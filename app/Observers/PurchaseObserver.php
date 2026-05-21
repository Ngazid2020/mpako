<?php

namespace App\Observers;

use App\Models\Purchase;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseObserver
{
    // ═══════════════════════════════════════════════
    // ÉVÉNEMENTS DE CYCLE DE VIE
    // ═══════════════════════════════════════════════

    /**
     * Quand un achat passe de 'pending' à 'completed'
     * → On met à jour le stock et la balance fournisseur
     */
    public function updated(Purchase $purchase): void
    {
        // On agit uniquement quand le statut passe à 'completed'
        if (!$this->isTransitionToCompleted($purchase)) {
            return;
        }

        $this->processPurchase($purchase);
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
     * Si un achat complété est annulé
     * → On libère le stock (mouvement de sortie compensatoire)
     *
     * ⚠️ La balance fournisseur est gérée par la Resource (action cancel)
     *    pour éviter le double comptage.
     */
    public function updating(Purchase $purchase): void
    {
        if (!$this->isTransitionToCancelled($purchase)) {
            return;
        }

        $this->reversePurchase($purchase);
    }

    // ═══════════════════════════════════════════════
    // DÉTECTION DE TRANSITION
    // ═══════════════════════════════════════════════

    /**
     * Détecte le passage à 'completed' depuis autre chose
     */
    private function isTransitionToCompleted(Purchase $purchase): bool
    {
        return $purchase->isDirty('status')
            && $purchase->status === 'completed'
            && $purchase->getOriginal('status') !== 'completed';
    }

    /**
     * Détecte le passage à 'cancelled' depuis 'completed'
     * (Seul ce cas nécessite de libérer le stock)
     */
    private function isTransitionToCancelled(Purchase $purchase): bool
    {
        return $purchase->isDirty('status')
            && $purchase->status === 'cancelled'
            && $purchase->getOriginal('status') === 'completed';
    }

    // ═══════════════════════════════════════════════
    // TRAITEMENT : Validation d'un achat
    // ═══════════════════════════════════════════════

    /**
     * Traitement principal lors de la validation d'un achat
     *
     * Étapes :
     * 1. Créer des mouvements de stock 'in' pour chaque produit
     * 2. Mettre à jour le prix d'achat (dernier connu)
     * 3. Mettre à jour la balance du fournisseur (si dette)
     *
     * Le tout dans une transaction pour garantir la cohérence.
     */
    private function processPurchase(Purchase $purchase): void
    {
        // Charger les relations nécessaires
        $purchase->load('items.product');

        // Vérifier qu'il y a des items à traiter
        if ($purchase->items->isEmpty()) {
            Log::warning("Purchase {$purchase->reference} validé sans items");
            return;
        }

        // Transaction pour garantir la cohérence
        DB::transaction(function () use ($purchase) {

            // ── 1. Traiter chaque item ──
            foreach ($purchase->items as $item) {

                // Sécurité : vérifier que le produit existe encore
                if (!$item->product) {
                    Log::error("Item {$item->id} sans produit lors de la validation Purchase {$purchase->reference}");
                    continue;
                }

                // Créer le mouvement de stock entrant
                StockMovement::create([
                    'shop_id'    => $purchase->shop_id,
                    'product_id' => $item->product_id,
                    'user_id'    => $purchase->user_id,
                    'type'       => 'in',
                    'quantity'   => $item->quantity,
                    'reason'     => "Achat {$purchase->reference}",
                ]);

                // Mettre à jour le prix d'achat (dernier connu)
                $item->product->update([
                    'buy_price' => $item->unit_cost,
                ]);
            }

            // ── 2. Mettre à jour la balance fournisseur ──
            if ($purchase->supplier_id && $purchase->debt_amount > 0) {
                $purchase->supplier->increment('balance', $purchase->debt_amount);
            }
        });
    }

    // ═══════════════════════════════════════════════
    // TRAITEMENT : Annulation d'un achat
    // ═══════════════════════════════════════════════

    /**
     * Inverse les effets d'un achat validé
     *
     * Étapes :
     * 1. Créer des mouvements de stock 'out' pour chaque produit
     *    (compense l'entrée précédente)
     *
     * ⚠️ La balance fournisseur n'est PAS modifiée ici
     *    car c'est géré dans la Resource (action 'cancel')
     */
    private function reversePurchase(Purchase $purchase): void
    {
        $purchase->load('items');

        if ($purchase->items->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($purchase) {

            foreach ($purchase->items as $item) {
                StockMovement::create([
                    'shop_id'    => $purchase->shop_id,
                    'product_id' => $item->product_id,
                    'user_id'    => auth()->id() ?? $purchase->user_id,
                    'type'       => 'out',
                    'quantity'   => $item->quantity,
                    'reason'     => "Annulation achat {$purchase->reference}",
                ]);
            }
        });
    }
}