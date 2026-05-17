<?php

namespace App\Observers;

use App\Models\CreditPayment;

class CreditPaymentObserver
{
    /**
     * Quand un remboursement est enregistré :
     * 1. Mettre à jour paid_amount et remaining_amount du crédit
     * 2. Changer le statut du crédit si nécessaire
     * 3. Réduire la balance du client
     */
    public function created(CreditPayment $payment): void
    {
        $credit = $payment->credit;

        // ── Mettre à jour les montants du crédit ──
        $newPaidAmount      = (float) $credit->paid_amount + (float) $payment->amount;
        $newRemainingAmount = max(0, (float) $credit->total_amount - $newPaidAmount);

        // ── Déterminer le nouveau statut ──
        $newStatus = match(true) {
            $newRemainingAmount <= 0 => 'paid',
            $newPaidAmount > 0      => 'partial',
            default                  => 'pending',
        };

        $credit->update([
            'paid_amount'      => $newPaidAmount,
            'remaining_amount' => $newRemainingAmount,
            'status'           => $newStatus,
        ]);

        // ── Réduire la balance du client ──
        $credit->customer->decrement('balance', $payment->amount);
    }

    /**
     * Si un remboursement est supprimé → on annule son effet
     */
    public function deleted(CreditPayment $payment): void
    {
        $credit = $payment->credit;

        $newPaidAmount      = max(0, (float) $credit->paid_amount - (float) $payment->amount);
        $newRemainingAmount = (float) $credit->total_amount - $newPaidAmount;

        $newStatus = match(true) {
            $newRemainingAmount <= 0 => 'paid',
            $newPaidAmount > 0      => 'partial',
            default                  => 'pending',
        };

        $credit->update([
            'paid_amount'      => $newPaidAmount,
            'remaining_amount' => $newRemainingAmount,
            'status'           => $newStatus,
        ]);

        // ── Restaurer la balance du client ──
        $credit->customer->increment('balance', $payment->amount);
    }
}