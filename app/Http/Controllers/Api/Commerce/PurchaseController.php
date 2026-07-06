<?php

namespace App\Http\Controllers\Api\Commerce;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\PurchaseResource;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\SupplierPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $shop      = $request->attributes->get('shop');
        $purchases = $shop->purchases()->with(['supplier', 'items'])->latest()->limit(100)->get();

        return response()->json(PurchaseResource::collection($purchases));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'supplier_id'          => 'nullable|integer',
            'items'                => 'required|array|min:1',
            'items.*.product_id'   => 'required|integer',
            'items.*.product_name' => 'required|string',
            'items.*.quantity'     => 'required|numeric|min:0.01',
            'items.*.unit_cost'    => 'required|numeric|min:0',
            'items.*.subtotal'     => 'required|numeric|min:0',
            'total_amount'         => 'required|numeric|min:0',
            'note'                 => 'nullable|string|max:500',
        ]);

        $shop = $request->attributes->get('shop');
        $user = $request->user();

        $purchase = DB::transaction(function () use ($request, $shop, $user) {
            $purchase = Purchase::create([
                'shop_id'        => $shop->id,
                'user_id'        => $user->id,
                'supplier_id'    => $request->supplier_id,
                'reference'      => Purchase::generateReference($shop->id),
                'status'         => 'pending',
                'payment_status' => 'unpaid',
                'total_amount'   => $request->total_amount,
                'paid_amount'    => 0,
                'debt_amount'    => $request->total_amount,
                'note'           => $request->note,
            ]);

            foreach ($request->items as $item) {
                PurchaseItem::create([
                    'purchase_id'  => $purchase->id,
                    'product_id'   => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity'     => $item['quantity'],
                    'unit_cost'    => $item['unit_cost'],
                    'subtotal'     => $item['subtotal'],
                ]);
            }

            return $purchase;
        });

        return response()->json(new PurchaseResource($purchase->load(['supplier', 'items'])), 201);
    }

    /**
     * Valide l'achat (pending → completed).
     * Le PurchaseObserver gère automatiquement : stock, buy_price, balance fournisseur.
     */
    public function complete(Request $request, int $purchaseId): JsonResponse
    {
        $shop     = $request->attributes->get('shop');
        $purchase = $shop->purchases()->findOrFail($purchaseId);

        if ($purchase->status !== 'pending') {
            return response()->json(['message' => 'Cet achat ne peut pas être validé (statut : ' . $purchase->status . ').'], 422);
        }

        $purchase->update(['status' => 'completed']);

        return response()->json([
            'status'  => 'completed',
            'message' => 'Achat validé. Le stock a été mis à jour.',
        ]);
    }

    /**
     * Enregistre un paiement de la dette fournisseur sur cet achat.
     * Le SupplierPaymentObserver met à jour paid_amount, debt_amount, payment_status et supplier.balance.
     */
    public function pay(Request $request, int $purchaseId): JsonResponse
    {
        $shop     = $request->attributes->get('shop');
        $user     = $request->user();
        $purchase = $shop->purchases()->findOrFail($purchaseId);

        if ((float) $purchase->debt_amount <= 0) {
            return response()->json(['message' => 'Cet achat est déjà entièrement payé.'], 422);
        }

        $validated = $request->validate([
            'amount'  => 'required|numeric|min:1|max:' . (float) $purchase->debt_amount,
            'paid_at' => 'nullable|date',
        ]);

        $amount = min((float) $validated['amount'], (float) $purchase->debt_amount);

        SupplierPayment::create([
            'purchase_id' => $purchase->id,
            'supplier_id' => $purchase->supplier_id,
            'user_id'     => $user->id,
            'amount'      => $amount,
            'paid_at'     => $validated['paid_at'] ?? now()->toDateString(),
        ]);

        $purchase->refresh();

        return response()->json([
            'paid'      => $amount,
            'remaining' => (float) $purchase->debt_amount,
            'status'    => $purchase->payment_status,
        ]);
    }
}
