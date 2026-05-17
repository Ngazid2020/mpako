<?php

namespace App\Observers;

use App\Models\Purchase;
use App\Models\StockMovement;

class PurchaseObserver
{
    /**
     * Quand un achat passe de 'pending' à 'completed'
     * → On met à jour le stock et la balance fournisseur
     */
    public function updated(Purchase $purchase): void
    {
        // On agit uniquement quand le statut passe à 'completed'
        $statusChanged    = $purchase->isDirty('status');
        $isNowCompleted   = $purchase->status === 'completed';
        $wasNotCompleted  = $purchase->getOriginal('status') !== 'completed';

        if ($statusChanged && $isNowCompleted && $wasNotCompleted) {
            $this->processPurchase($purchase);
        }
    }

    /**
     * Si l'achat est créé directement en 'completed'
     */
    public function created(Purchase $purchase): void
    {
        if ($purchase->status === 'completed') {
            $this->processPurchase($purchase);
        }
    }

    /**
     * Traitement principal lors de la validation
     */
    private function processPurchase(Purchase $purchase): void
    {
        // Charger les lignes d'achat avec les produits
        $purchase->load('items.product');

        foreach ($purchase->items as $item) {
            // ── 1. Créer un mouvement de stock 'in' ──
            StockMovement::create([
                'shop_id'    => $purchase->shop_id,
                'product_id' => $item->product_id,
                'user_id'    => $purchase->user_id,
                'type'       => 'in',
                'quantity'   => $item->quantity,
                'reason'     => "Achat {$purchase->reference}",
            ]);

            // ── 2. Mettre à jour le prix d'achat du produit ──
            // On garde toujours le dernier prix d'achat connu
            $item->product->update([
                'buy_price' => $item->unit_cost,
            ]);
        }

        // ── 3. Mettre à jour la balance du fournisseur ──
        if ($purchase->supplier_id && $purchase->debt_amount > 0) {
            $purchase->supplier->increment('balance', $purchase->debt_amount);
        }
    }

    /**
     * Si un achat complété est annulé
     * → On soustrait le stock (annulation)
     */
    public function updating(Purchase $purchase): void
    {
        $statusChanged  = $purchase->isDirty('status');
        $isNowCancelled = $purchase->status === 'cancelled';
        $wasCompleted   = $purchase->getOriginal('status') === 'completed';

        if ($statusChanged && $isNowCancelled && $wasCompleted) {
            $purchase->load('items');

            foreach ($purchase->items as $item) {
                // Mouvement de sortie pour annuler l'entrée
                StockMovement::create([
                    'shop_id'    => $purchase->shop_id,
                    'product_id' => $item->product_id,
                    'user_id'    => auth()->id(),
                    'type'       => 'out',
                    'quantity'   => $item->quantity,
                    'reason'     => "Annulation achat {$purchase->reference}",
                ]);
            }

            // Réduire la balance du fournisseur si dette existait
            if ($purchase->supplier_id && $purchase->debt_amount > 0) {
                $purchase->supplier->decrement('balance', $purchase->debt_amount);
            }
        }
    }
}