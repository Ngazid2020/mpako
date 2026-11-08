<?php

namespace App\Http\Controllers\Api\Commerce;

use App\Http\Controllers\Api\Concerns\InteractsWithShop;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CreditResource;
use App\Models\CreditPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreditController extends Controller
{
    use InteractsWithShop;

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAny($request, 'view_any_credit');

        $credits = $this->shop($request)->credits()->with('customer')->latest()->get();

        return response()->json(CreditResource::collection($credits));
    }

    public function pay(Request $request, string $shopSlug, int $creditId): JsonResponse
    {
        // Encaisser un remboursement : autorisé au caissier (create_credit) comme au gérant
        $this->authorizeAny($request, 'create_credit', 'update_credit');

        $request->validate([
            'amount'  => 'required|numeric|min:1',
            'paid_at' => 'nullable|date',
            'note'    => 'nullable|string|max:255',
        ]);

        $shop = $this->shop($request);
        $user = $request->user();

        return DB::transaction(function () use ($request, $shop, $user, $creditId) {
            $credit = $shop->credits()->lockForUpdate()->findOrFail($creditId);

            if ($credit->status === 'paid') {
                return response()->json(['message' => 'Ce crédit est déjà soldé.'], 422);
            }

            if ((float) $request->amount > (float) $credit->remaining_amount) {
                return response()->json(['message' => 'Montant supérieur au reste dû.'], 422);
            }

            // CreditPaymentObserver gère credit + customer.balance automatiquement
            CreditPayment::create([
                'credit_id' => $credit->id,
                'user_id'   => $user->id,
                'amount'    => $request->amount,
                'paid_at'   => $request->paid_at ?? now()->toDateString(),
                'note'      => $request->note,
            ]);

            return response()->json(new CreditResource($credit->fresh()));
        });
    }
}
