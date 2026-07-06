<?php

namespace App\Http\Controllers\Api\Commerce;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\SupplierResource;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $shop      = $request->attributes->get('shop');
        $suppliers = $shop->suppliers()->orderBy('name')->get();

        return response()->json(SupplierResource::collection($suppliers));
    }

    public function pay(Request $request, string $shopSlug, int $supplierId): JsonResponse
    {
        $request->validate([
            'amount'  => 'required|numeric|min:1',
            'paid_at' => 'nullable|date',
            'note'    => 'nullable|string|max:500',
        ]);

        $shop     = $request->attributes->get('shop');
        $supplier = $shop->suppliers()->findOrFail($supplierId);

        if ($supplier->balance <= 0) {
            return response()->json(['message' => 'Aucune dette à régler.'], 422);
        }

        if ($request->amount > $supplier->balance) {
            return response()->json(['message' => 'Montant supérieur à la dette.'], 422);
        }

        $purchase = $shop->purchases()
            ->where('supplier_id', $supplier->id)
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->latest()
            ->first();

        if (! $purchase) {
            return response()->json(['message' => 'Aucun achat en attente de paiement.'], 422);
        }

        // SupplierPaymentObserver gère purchase + supplier.balance automatiquement
        SupplierPayment::create([
            'purchase_id' => $purchase->id,
            'amount'      => $request->amount,
            'paid_at'     => $request->paid_at ?? now(),
            'note'        => $request->note,
        ]);

        return response()->json(new SupplierResource($supplier->fresh()), 201);
    }
}
