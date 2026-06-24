<?php

namespace App\Observers;

use App\Models\StockMovement;
use App\Models\User;
use App\Notifications\LowStockNotification;
use Filament\Facades\Filament;

class StockMovementObserver
{
    public function creating(StockMovement $movement): void
    {
        $product = $movement->product;

        // ── Calculer stock_before et stock_after ──
        $stockBefore = (float) $product->stock_qty;
        $movement->stock_before = $stockBefore;

        $stockAfter = match($movement->type) {
            'in'         => $stockBefore + (float) $movement->quantity,
            'out'        => $stockBefore - (float) $movement->quantity,
            'adjustment' => (float) $movement->quantity, // La quantité = nouveau stock réel
        };

        $movement->stock_after = max(0, $stockAfter); // Jamais négatif

        // ── Ajouter shop_id et user_id automatiquement ──
        $movement->shop_id ??= Filament::getTenant()?->getKey();
        $movement->user_id ??= auth()->id();
    }

    public function created(StockMovement $movement): void
    {
        // ── Mettre à jour le stock du produit ──
        $product = $movement->product;
        $product->update(['stock_qty' => $movement->stock_after]);

        // ── Alerte stock faible (push notification) ──
        if ($movement->type === 'out' && $product->stock_qty <= $product->stock_alert) {
            User::whereHas('shops', fn($q) => $q->where('shops.id', $movement->shop_id))
                ->get()
                ->each(fn(User $user) => $user->notify(new LowStockNotification($product)));
        }
    }
}