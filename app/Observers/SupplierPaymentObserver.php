<?php

namespace App\Observers;

use App\Models\SupplierPayment;

class SupplierPaymentObserver
{
    /**
     * Quand un paiement fournisseur est enregistré :
     * 1. Mettre à jour paid_amount et debt_amount du Purchase
     * 2. Mettre à jour le payment_status du Purchase
     * 3. Réduire la balance du Supplier
     */
    public function created(SupplierPayment $payment): void
    {
        $purchase = $payment->purchase;

        // ── Calculer les nouveaux montants ──
        $newPaidAmount = (float) $purchase->paid_amount + (float) $payment->amount;
        $newDebtAmount = max(0, (float) $purchase->total_amount - $newPaidAmount);

        // ── Déterminer le nouveau payment_status ──
        $newPaymentStatus = match(true) {
            $newDebtAmount <= 0   => 'paid',
            $newPaidAmount > 0    => 'partial',
            default               => 'unpaid',
        };

        // ── Mettre à jour le Purchase ──
        $purchase->update([
            'paid_amount'    => $newPaidAmount,
            'debt_amount'    => $newDebtAmount,
            'payment_status' => $newPaymentStatus,
        ]);

        // ── Réduire la balance du fournisseur ──
        $payment->supplier->decrement('balance', $payment->amount);
    }

    /**
     * Si un paiement est supprimé → on annule son effet
     */
    public function deleted(SupplierPayment $payment): void
    {
        $purchase = $payment->purchase;

        $newPaidAmount = max(0, (float) $purchase->paid_amount - (float) $payment->amount);
        $newDebtAmount = (float) $purchase->total_amount - $newPaidAmount;

        $newPaymentStatus = match(true) {
            $newDebtAmount <= 0 => 'paid',
            $newPaidAmount > 0  => 'partial',
            default             => 'unpaid',
        };

        $purchase->update([
            'paid_amount'    => $newPaidAmount,
            'debt_amount'    => $newDebtAmount,
            'payment_status' => $newPaymentStatus,
        ]);

        // ── Restaurer la balance du fournisseur ──
        $payment->supplier->increment('balance', $payment->amount);
    }
}