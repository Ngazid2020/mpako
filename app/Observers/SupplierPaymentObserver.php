<?php

namespace App\Observers;

use App\Models\Purchase;
use App\Models\SupplierPayment;

class SupplierPaymentObserver
{
    /**
     * Garde-fous avant insertion :
     *  - seul un achat VALIDÉ porte une dette fournisseur (pending/cancelled refusés)
     *  - montant plafonné à la dette restante (pour que `deleted` annule exactement l'effet)
     *  - supplier_id / user_id complétés (colonnes NOT NULL)
     */
    public function creating(SupplierPayment $payment): void
    {
        $purchase = Purchase::whereKey($payment->purchase_id)->lockForUpdate()->firstOrFail();

        if ($purchase->status !== 'completed') {
            throw new \RuntimeException("Seul un achat validé peut être payé ({$purchase->reference} : {$purchase->status}).");
        }

        if (! $purchase->supplier_id) {
            throw new \RuntimeException("L'achat {$purchase->reference} n'a pas de fournisseur.");
        }

        if ((float) $purchase->debt_amount <= 0) {
            throw new \RuntimeException("L'achat {$purchase->reference} est déjà entièrement payé.");
        }

        $payment->amount      = min((float) $payment->amount, (float) $purchase->debt_amount);
        $payment->supplier_id = $purchase->supplier_id;
        $payment->user_id   ??= auth()->id() ?? $purchase->user_id;
    }

    /**
     * 1. Met à jour paid_amount / debt_amount / payment_status de l'achat
     * 2. Réduit la balance du fournisseur
     */
    public function created(SupplierPayment $payment): void
    {
        $purchase = $payment->purchase;
        $amount   = (float) $payment->amount;

        $newPaid = (float) $purchase->paid_amount + $amount;

        // Purchase::saving recalcule payment_status
        $purchase->update([
            'paid_amount' => $newPaid,
            'debt_amount' => max(0, round((float) $purchase->total_amount - $newPaid, 2)),
        ]);

        $payment->supplier->decrement('balance', $amount);
    }

    /**
     * Si un paiement est supprimé → on annule son effet
     */
    public function deleted(SupplierPayment $payment): void
    {
        $purchase = $payment->purchase;

        // Achat annulé : sa dette a déjà été soldée à l'annulation, on ne la ressuscite pas.
        if (! $purchase || $purchase->status !== 'completed') {
            return;
        }

        $amount  = (float) $payment->amount;
        $newPaid = max(0, (float) $purchase->paid_amount - $amount);

        $purchase->update([
            'paid_amount' => $newPaid,
            'debt_amount' => max(0, round((float) $purchase->total_amount - $newPaid, 2)),
        ]);

        $payment->supplier?->increment('balance', $amount);
    }
}
