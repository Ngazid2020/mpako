<?php

namespace App\Observers;

use App\Models\Credit;

class CreditObserver
{
    /**
     * Quand un crédit est créé
     * → Augmenter la balance du client
     */
    public function created(Credit $credit): void
    {
        $credit->customer->increment('balance', $credit->total_amount);
    }

    /**
     * Quand un crédit est supprimé
     * → Réduire la balance du client
     */
    public function deleted(Credit $credit): void
    {
        $credit->customer->decrement('balance', $credit->remaining_amount);
    }
}