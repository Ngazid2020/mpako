<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Notifications\LowStockNotification;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockMovementObserver
{
    public function creating(StockMovement $movement): void
    {
        // ── shop_id / user_id automatiques ──
        $movement->shop_id ??= Filament::getTenant()?->getKey();
        $movement->user_id ??= auth()->id();

        // Ligne produit verrouillée : deux ventes simultanées (caisse + sync mobile)
        // ne doivent pas s'écraser mutuellement le stock (effectif dans une transaction).
        $product = Product::whereKey($movement->product_id)->lockForUpdate()->first();

        if (! $product) {
            throw new \InvalidArgumentException("Produit introuvable pour le mouvement de stock.");
        }

        // Anti fuite cross-tenant : un mouvement ne peut viser que le stock de son propre shop
        if ($movement->shop_id && (int) $product->shop_id !== (int) $movement->shop_id) {
            throw new \InvalidArgumentException("Le produit n'appartient pas à ce commerce.");
        }

        $movement->shop_id ??= $product->shop_id;

        // ── Calculer stock_before et stock_after ──
        $stockBefore = (float) $product->stock_qty;
        $movement->stock_before = $stockBefore;

        $stockAfter = match ($movement->type) {
            'in'         => $stockBefore + (float) $movement->quantity,
            'out'        => $stockBefore - (float) $movement->quantity,
            'adjustment' => (float) $movement->quantity,
            default      => throw new \InvalidArgumentException("Type de mouvement invalide : {$movement->type}"),
        };

        if ($stockAfter < 0) {
            // Le stock ne descend jamais sous 0 (choix métier) ; on trace l'écart pour audit.
            Log::warning("Stock négatif évité pour le produit {$product->id} ({$product->name}) : {$stockAfter} ramené à 0.");
        }

        $movement->stock_after = max(0, $stockAfter);
    }

    public function created(StockMovement $movement): void
    {
        $product = $movement->product;
        $product->update(['stock_qty' => $movement->stock_after]);

        // ── Alerte stock faible : uniquement au FRANCHISSEMENT du seuil ──
        // (sinon chaque vente d'un produit déjà bas re-notifie tous les membres)
        $alert = (float) $product->stock_alert;
        $crossedThreshold = $movement->type !== 'in'
            && $alert > 0
            && (float) $movement->stock_before > $alert
            && (float) $movement->stock_after <= $alert;

        if (! $crossedThreshold) {
            return;
        }

        // Envoyé après commit : une erreur de push ne doit jamais annuler la vente,
        // et on ne fait pas d'appels HTTP pendant que des verrous sont tenus.
        DB::afterCommit(function () use ($movement, $product) {
            try {
                User::whereHas('shops', fn ($q) => $q->where('shops.id', $movement->shop_id))
                    ->get()
                    ->each(fn (User $user) => $user->notify(new LowStockNotification($product)));
            } catch (\Throwable $e) {
                Log::error('Échec notification stock faible', ['product_id' => $product->id, 'error' => $e->getMessage()]);
            }
        });
    }
}
