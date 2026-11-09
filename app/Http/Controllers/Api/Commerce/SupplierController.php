<?php

namespace App\Http\Controllers\Api\Commerce;

use App\Http\Controllers\Api\Concerns\InteractsWithShop;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\SupplierResource;
use App\Models\Shop;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplierController extends Controller
{
    use InteractsWithShop;

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAny($request, 'view_any_supplier');

        $suppliers = $this->shop($request)->suppliers()->orderBy('name')->get();

        return response()->json(SupplierResource::collection($suppliers));
    }

    public function pay(Request $request, string $shopSlug, int $supplierId): JsonResponse
    {
        $this->authorizeAny($request, 'update_supplier', 'update_purchase');

        $validated = $request->validate([
            'amount'  => 'required|numeric|min:1',
            'paid_at' => 'nullable|date',
            'note'    => 'nullable|string|max:255',
        ]);

        $shop = $this->shop($request);

        $supplier = DB::transaction(function () use ($shop, $supplierId, $validated, $request) {
            $supplier = $shop->suppliers()->lockForUpdate()->findOrFail($supplierId);

            static::allocateSupplierPayment(
                $shop,
                $supplier,
                $request->user(),
                (float) $validated['amount'],
                $validated['paid_at'] ?? null,
                $validated['note'] ?? null,
            );

            return $supplier->fresh();
        });

        return response()->json(new SupplierResource($supplier), 201);
    }

    /**
     * Répartit un paiement global sur les achats VALIDÉS impayés du fournisseur,
     * du plus ancien au plus récent (FIFO). Refuse tout montant supérieur à la dette réelle.
     *
     * @return array<int, array{purchase_id:int, amount:float}>
     */
    public static function allocateSupplierPayment(
        Shop $shop,
        Supplier $supplier,
        User $user,
        float $amount,
        ?string $paidAt = null,
        ?string $note = null,
    ): array {
        $purchases = $shop->purchases()
            ->where('supplier_id', $supplier->id)
            ->where('status', 'completed')
            ->where('debt_amount', '>', 0)
            ->orderBy('purchased_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $totalDebt = round((float) $purchases->sum('debt_amount'), 2);

        if ($totalDebt <= 0) {
            throw ValidationException::withMessages(['amount' => 'Aucune dette à régler pour ce fournisseur.']);
        }

        if (round($amount, 2) > $totalDebt) {
            throw ValidationException::withMessages(['amount' => "Montant supérieur à la dette ({$totalDebt})."]);
        }

        $remaining   = round($amount, 2);
        $allocations = [];

        foreach ($purchases as $purchase) {
            if ($remaining <= 0) {
                break;
            }

            $part = min($remaining, (float) $purchase->debt_amount);

            SupplierPayment::create([
                'purchase_id' => $purchase->id,
                'supplier_id' => $supplier->id,
                'user_id'     => $user->id,
                'amount'      => $part,
                'paid_at'     => $paidAt ?? now()->toDateString(),
                'note'        => $note,
            ]);

            $allocations[] = ['purchase_id' => $purchase->id, 'amount' => $part];
            $remaining     = round($remaining - $part, 2);
        }

        return $allocations;
    }
}
