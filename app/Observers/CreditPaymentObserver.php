<?php

namespace App\Observers;

use App\Models\Credit;
use App\Models\CreditPayment;

class CreditPaymentObserver
{
    /**
     * Le montant stocké est plafonné au reste dû AVANT insertion :
     * ainsi `deleted` peut annuler exactement ce qui a été appliqué
     * (avant : un trop-perçu supprimé gonflait la balance client).
     */
    public function creating(CreditPayment $payment): void
    {
        $credit = Credit::whereKey($payment->credit_id)->lockForUpdate()->firstOrFail();

        if ((float) $credit->remaining_amount <= 0) {
            throw new \RuntimeException("Le crédit {$credit->reference} est déjà soldé.");
        }

        $payment->amount  = min((float) $payment->amount, (float) $credit->remaining_amount);
        $payment->user_id ??= auth()->id() ?? $credit->user_id;
    }

    /**
     * 1. Met à jour paid_amount / remaining_amount / status du crédit
     * 2. Réduit la balance du client
     */
    public function created(CreditPayment $payment): void
    {
        $credit = $payment->credit;
        $amount = (float) $payment->amount;

        $this->applyToCredit($credit, (float) $credit->paid_amount + $amount);

        $credit->customer->decrement('balance', $amount);
    }

    /**
     * Si un remboursement est supprimé → on annule son effet
     */
    public function deleted(CreditPayment $payment): void
    {
        $credit = $payment->credit;

        if (! $credit) {
            return; // crédit supprimé en cascade : CreditObserver a déjà traité la balance
        }

        $amount = (float) $payment->amount;

        $this->applyToCredit($credit, max(0, (float) $credit->paid_amount - $amount));

        $credit->customer->increment('balance', $amount);
    }

    private function applyToCredit(Credit $credit, float $newPaidAmount): void
    {
        $newRemaining = max(0, round((float) $credit->total_amount - $newPaidAmount, 2));

        $credit->updateQuietly([
            'paid_amount'      => $newPaidAmount,
            'remaining_amount' => $newRemaining,
            'status'           => Credit::deriveStatus($newPaidAmount, $newRemaining),
        ]);
    }
}
