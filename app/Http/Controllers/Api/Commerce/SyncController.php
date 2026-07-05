<?php

namespace App\Http\Controllers\Api\Commerce;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CreditResource;
use App\Http\Resources\Api\CustomerResource;
use App\Http\Resources\Api\ProductResource;
use App\Http\Resources\Api\SaleResource;
use App\Models\Credit;
use App\Models\CreditPayment;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SyncController extends Controller
{
    /**
     * Sync descendante — envoie tout ce qui a changé depuis `?since=` (ISO timestamp).
     * Sans `since` → snapshot complet (premier démarrage).
     */
    public function pull(Request $request): JsonResponse
    {
        $shop  = $request->attributes->get('shop');
        $since = $request->query('since');

        $query = fn($relation) => $since
            ? $shop->$relation()->where('updated_at', '>', $since)
            : $shop->$relation();

        return response()->json([
            'products'  => ProductResource::collection(
                $query('products')->with('unit')->get()
            ),
            'customers' => CustomerResource::collection(
                $query('customers')->get()
            ),
            'credits'   => CreditResource::collection(
                $query('credits')->get()
            ),
            'sales'     => SaleResource::collection(
                $query('sales')->with('items')->get()
            ),
            'synced_at' => now()->toISOString(),
        ]);
    }

    /**
     * Sync montante — reçoit un tableau d'opérations offline et les exécute dans l'ordre.
     * Les observers existants maintiennent la cohérence (stock, balances, etc.).
     */
    public function push(Request $request): JsonResponse
    {
        $request->validate([
            'operations'                    => 'required|array',
            'operations.*.type'             => 'required|string',
            'operations.*.local_id'         => 'required|string',
            'operations.*.payload'          => 'required|array',
            'operations.*.created_offline_at' => 'required|string',
        ]);

        $shop    = $request->attributes->get('shop');
        $user    = $request->user();
        $results = [];

        // Trier par date offline pour respecter l'ordre chronologique
        $operations = collect($request->operations)
            ->sortBy('created_offline_at')
            ->values();

        foreach ($operations as $op) {
            try {
                $result = DB::transaction(function () use ($op, $shop, $user) {
                    return match ($op['type']) {
                        'create_sale'         => $this->createSale($op, $shop, $user),
                        'create_credit'       => $this->createCredit($op, $shop, $user),
                        'create_credit_payment' => $this->createCreditPayment($op, $shop, $user),
                        'create_stock_adjustment' => $this->createStockAdjustment($op, $shop, $user),
                        'create_purchase'     => $this->createPurchase($op, $shop, $user),
                        default               => throw new \InvalidArgumentException("Type inconnu : {$op['type']}"),
                    };
                });

                $results[] = [
                    'local_id' => $op['local_id'],
                    'status'   => 'synced',
                    'data'     => $result,
                ];
            } catch (\Throwable $e) {
                $results[] = [
                    'local_id' => $op['local_id'],
                    'status'   => 'failed',
                    'error'    => $e->getMessage(),
                ];
            }
        }

        return response()->json(['results' => $results]);
    }

    // ─── Handlers individuels ───────────────────────────────────────────

    private function createSale(array $op, $shop, $user): array
    {
        $p = $op['payload'];

        $sale = Sale::create([
            'shop_id'       => $shop->id,
            'user_id'       => $user->id,
            'customer_id'   => $p['customer_id'] ?? null,
            'payment_type'  => $p['payment_type'] ?? 'cash',
            'reference'     => Sale::generateReference($shop->id),
            'status'        => 'completed',
            'total_amount'  => $p['total_amount'],
            'paid_amount'   => $p['paid_amount'] ?? $p['total_amount'],
            'change_amount' => $p['change_amount'] ?? 0,
            'note'          => $p['note'] ?? null,
        ]);

        foreach ($p['items'] as $item) {
            SaleItem::create([
                'sale_id'      => $sale->id,
                'product_id'   => $item['product_id'],
                'product_name' => $item['product_name'],
                'quantity'     => $item['quantity'],
                'unit_price'   => $item['unit_price'],
                'subtotal'     => $item['subtotal'],
            ]);

            // Le StockMovementObserver gère stock_qty automatiquement
            StockMovement::create([
                'shop_id'    => $shop->id,
                'product_id' => $item['product_id'],
                'user_id'    => $user->id,
                'type'       => 'out',
                'quantity'   => $item['quantity'],
                'reason'     => "Vente {$sale->reference} (sync offline)",
            ]);
        }

        return ['id' => $sale->id, 'reference' => $sale->reference];
    }

    private function createCredit(array $op, $shop, $user): array
    {
        $p = $op['payload'];

        // Récupérer le sale_id officiel via local_sale_id si fourni
        $saleId = $p['sale_id'] ?? null;

        $credit = Credit::create([
            'shop_id'          => $shop->id,
            'sale_id'          => $saleId,
            'customer_id'      => $p['customer_id'],
            'user_id'          => $user->id,
            'reference'        => Credit::generateReference($shop->id),
            'status'           => 'pending',
            'total_amount'     => $p['total_amount'],
            'paid_amount'      => 0,
            'remaining_amount' => $p['total_amount'],
            'due_date'         => $p['due_date'] ?? null,
            'description'      => $p['description'] ?? 'Crédit enregistré hors ligne',
            'note'             => $p['note'] ?? null,
        ]);

        // CreditObserver augmente customer.balance automatiquement

        return ['id' => $credit->id, 'reference' => $credit->reference];
    }

    private function createCreditPayment(array $op, $shop, $user): array
    {
        $p = $op['payload'];

        $credit = $shop->credits()->findOrFail($p['credit_id']);

        $payment = CreditPayment::create([
            'credit_id' => $credit->id,
            'amount'    => $p['amount'],
            'paid_at'   => $p['paid_at'] ?? now(),
            'note'      => $p['note'] ?? null,
        ]);

        // CreditPaymentObserver met à jour credit + customer.balance automatiquement

        return ['id' => $payment->id, 'credit_id' => $credit->id];
    }

    private function createStockAdjustment(array $op, $shop, $user): array
    {
        $p = $op['payload'];

        $movement = StockMovement::create([
            'shop_id'    => $shop->id,
            'product_id' => $p['product_id'],
            'user_id'    => $user->id,
            'type'       => $p['type'], // 'in' | 'out' | 'adjustment'
            'quantity'   => $p['quantity'],
            'reason'     => $p['reason'] ?? 'Ajustement offline',
        ]);

        return ['id' => $movement->id];
    }

    private function createPurchase(array $op, $shop, $user): array
    {
        $p = $op['payload'];

        $purchase = Purchase::create([
            'shop_id'         => $shop->id,
            'user_id'         => $user->id,
            'supplier_id'     => $p['supplier_id'] ?? null,
            'reference'       => Purchase::generateReference($shop->id),
            'status'          => $p['status'] ?? 'pending',
            'payment_status'  => 'unpaid',
            'total_amount'    => $p['total_amount'],
            'paid_amount'     => 0,
            'note'            => $p['note'] ?? null,
        ]);

        foreach ($p['items'] as $item) {
            PurchaseItem::create([
                'purchase_id' => $purchase->id,
                'product_id'  => $item['product_id'],
                'quantity'    => $item['quantity'],
                'unit_cost'   => $item['unit_cost'],
                'subtotal'    => $item['subtotal'],
            ]);
        }

        // PurchaseObserver gère stock si status = 'completed'

        return ['id' => $purchase->id, 'reference' => $purchase->reference];
    }
}
