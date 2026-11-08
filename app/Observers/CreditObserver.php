<?php

namespace App\Observers;

use App\Models\Credit;

class CreditObserver
{
    /**
     * Crédit créé → la balance du client augmente du montant dû
     */
    public function created(Credit $credit): void
    {
        $credit->customer->increment('balance', $credit->remaining_amount ?? $credit->total_amount);
    }

    /**
     * Modification du montant total (page d'édition) : on recalcule le reste dû
     * et le statut à partir des paiements déjà reçus.
     */
    public function updating(Credit $credit): void
    {
        if (! $credit->isDirty('total_amount')) {
            return;
        }

        $paid      = (float) $credit->paid_amount;
        $remaining = max(0, round((float) $credit->total_amount - $paid, 2));

        $credit->remaining_amount = $remaining;
        $credit->status           = Credit::deriveStatus($paid, $remaining);
    }

    /**
     * … puis on répercute l'écart de reste dû sur la balance client.
     * (Les paiements passent par updateQuietly et ne déclenchent pas ce hook.)
     */
    public function updated(Credit $credit): void
    {
        if (! $credit->wasChanged('remaining_amount')) {
            return;
        }

        $delta = (float) $credit->remaining_amount - (float) $credit->getOriginal('remaining_amount');

        if ($delta > 0) {
            $credit->customer->increment('balance', $delta);
        } elseif ($delta < 0) {
            $credit->customer->decrement('balance', abs($delta));
        }
    }

    /**
     * Crédit supprimé → on retire le reste dû de la balance du client
     */
    public function deleted(Credit $credit): void
    {
        $credit->customer?->decrement('balance', $credit->remaining_amount);
    }
}
