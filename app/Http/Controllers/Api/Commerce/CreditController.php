<?php

namespace App\Http\Controllers\Api\Commerce;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CreditResource;
use App\Models\Credit;
use App\Models\CreditPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreditController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $shop    = $request->attributes->get('shop');
        $credits = $shop->credits()->with('customer')->latest()->get();

        return response()->json(CreditResource::collection($credits));
    }

    public function pay(Request $request, string $shopSlug, int $creditId): JsonResponse
    {
        $request->validate([
            'amount'  => 'required|numeric|min:1',
            'paid_at' => 'nullable|date',
            'note'    => 'nullable|string|max:500',
        ]);

        $shop   = $request->attributes->get('shop');
        $credit = $shop->credits()->findOrFail($creditId);

        if ($credit->status === 'paid') {
            return response()->json(['message' => 'Ce crédit est déjà soldé.'], 422);
        }

        if ($request->amount > $credit->remaining_amount) {
            return response()->json(['message' => 'Montant supérieur au reste dû.'], 422);
        }

        // CreditPaymentObserver gère credit + customer.balance automatiquement
        CreditPayment::create([
            'credit_id' => $credit->id,
            'amount'    => $request->amount,
            'paid_at'   => $request->paid_at ?? now(),
            'note'      => $request->note,
        ]);

        return response()->json(new CreditResource($credit->fresh()));
    }
}
