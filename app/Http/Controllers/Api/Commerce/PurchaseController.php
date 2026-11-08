<?php

namespace App\Http\Controllers\Api\Commerce;

use App\Http\Controllers\Api\Concerns\InteractsWithShop;
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
    use InteractsWithShop;

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAny($request, 'view_any_purchase');

        $shop      = $this->shop($request);
        $purchases = $shop->purchases()->with(['supplier', 'items'])->latest()->limit(100)->get();

        return response()->json(PurchaseResource::collection($purchases));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAny($request, 'create_purchase');

        $shop = $this->shop($request);

        $request->validate([
            'supplier_id'          => ['nullable', 'integer', $this->inShop('suppliers', $shop)],
            'items'                => 'required|array|min:1',
            'items.*.product_id'   => ['required', 'integer', $this->inShop('products', $shop)],
            'items.*.product_name' => 'required|string',
            'items.*.quantity'     => 'required|numeric|min:0.01',
            'items.*.unit_cost'    => 'required|numeric|min:0',
            'items.*.subtotal'     => 'nullable|numeric|min:0',
            'purchased_at'         => 'nullable|date',
            'note'                 => 'nullable|string|max:500',
        ]);

        $user = $request->user();

        $purchase = DB::transaction(function () use ($request, $shop, $user) {
            // Total recalculé côté serveur (PurchaseItem recalcule aussi chaque subtotal)
            $total = collect($request->items)
                ->sum(fn ($i) => round((float) $i['quantity'] * (float) $i['unit_cost'], 2));

            $purchase = Purchase::create([
                'shop_id'        => $shop->id,
                'user_id'        => $user->id,
                'supplier_id'    => $request->supplier_id,
                'reference'      => Purchase::generateReference($shop->id),
                'status'         => 'pending',
                'payment_status' => 'unpaid',
                'total_amount'   => $total,
                'paid_amount'    => 0,
                'debt_amount'    => $total,
                'purchased_at'   => $request->purchased_at ?? now()->toDateString(),
                'note'           => $request->note,
            ]);

            foreach ($request->items as $item) {
                PurchaseItem::create([
                    'purchase_id'  => $purchase->id,
                    'product_id'   => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity'     => $item['quantity'],
                    'unit_cost'    => $item['unit_cost'],
                ]);
            }

            return $purchase;
        });

        return response()->json(new PurchaseResource($purchase->load(['supplier', 'items'])), 201);
    }

    /**
     * Valide l'achat (pending → completed).
     * Le PurchaseObserver gère automatiquement : stock, buy_price, balance fournisseur.
     *
     * ⚠️ Les paramètres de route sont passés par position : {shop} puis {purchase}.
     */
    public function complete(Request $request, string $shopSlug, int $purchaseId): JsonResponse
    {
        $this->authorizeAny($request, 'update_purchase');

        $shop = $this->shop($request);

        return DB::transaction(function () use ($shop, $purchaseId) {
            $purchase = $shop->purchases()->lockForUpdate()->findOrFail($purchaseId);

            if ($purchase->status !== 'pending') {
                return response()->json(['message' => 'Cet achat ne peut pas être validé (statut : ' . $purchase->status . ').'], 422);
            }

            $purchase->update(['status' => 'completed']);

            return response()->json([
                'status'  => 'completed',
                'message' => 'Achat validé. Le stock a été mis à jour.',
            ]);
        });
    }

    /**
     * Enregistre un paiement de la dette fournisseur sur cet achat.
     * Le SupplierPaymentObserver met à jour paid_amount, debt_amount, payment_status et supplier.balance.
     */
    public function pay(Request $request, string $shopSlug, int $purchaseId): JsonResponse
    {
        $this->authorizeAny($request, 'update_purchase', 'update_supplier');

        $validated = $request->validate([
            'amount'  => 'required|numeric|min:1',
            'paid_at' => 'nullable|date',
            'note'    => 'nullable|string|max:255',
        ]);

        $shop = $this->shop($request);
        $user = $request->user();

        return DB::transaction(function () use ($shop, $user, $purchaseId, $validated) {
            $purchase = $shop->purchases()->lockForUpdate()->findOrFail($purchaseId);

            // La dette fournisseur n'existe qu'après validation de l'achat
            if ($purchase->status !== 'completed') {
                return response()->json(['message' => "Seul un achat validé peut être payé (statut : {$purchase->status})."], 422);
            }

            if (! $purchase->supplier_id) {
                return response()->json(['message' => 'Cet achat n\'a pas de fournisseur : aucune dette à régler.'], 422);
            }

            if ((float) $purchase->debt_amount <= 0) {
                return response()->json(['message' => 'Cet achat est déjà entièrement payé.'], 422);
            }

            if ((float) $validated['amount'] > (float) $purchase->debt_amount) {
                return response()->json(['message' => 'Montant supérieur au reste dû (' . (float) $purchase->debt_amount . ').'], 422);
            }

            $amount = (float) $validated['amount'];

            SupplierPayment::create([
                'purchase_id' => $purchase->id,
                'supplier_id' => $purchase->supplier_id,
                'user_id'     => $user->id,
                'amount'      => $amount,
                'paid_at'     => $validated['paid_at'] ?? now()->toDateString(),
                'note'        => $validated['note'] ?? null,
            ]);

            $purchase->refresh();

            return response()->json([
                'paid'      => $amount,
                'remaining' => (float) $purchase->debt_amount,
                'status'    => $purchase->payment_status,
            ]);
        });
    }
}
