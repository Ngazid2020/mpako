<?php

namespace App\Observers;

use App\Models\Purchase;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Cycle de vie d'un achat :
 *
 *   pending ──(complete)──▶ completed ──(cancel)──▶ cancelled
 *      └────────────(cancel)─────────────────────▶ cancelled
 *
 * - pending → completed : entrée en stock, buy_price, +dette fournisseur
 * - completed → cancelled : sortie de stock compensatoire, −dette restante fournisseur
 * - pending → cancelled : aucune incidence stock/fournisseur (la dette n'a jamais été comptée)
 *
 * ⚠️ Un achat doit être créé en 'pending' puis validé APRÈS l'enregistrement de ses lignes
 *    (voir CreatePurchase::afterCreate et SyncController::createPurchase).
 */
class PurchaseObserver
{
    public function created(Purchase $purchase): void
    {
        // Cas résiduel (seeders/scripts) : création directe en 'completed' avec lignes déjà présentes.
        if ($purchase->status === 'completed') {
            $this->processPurchase($purchase);
        }
    }

    public function updated(Purchase $purchase): void
    {
        if (! $purchase->wasChanged('status')) {
            return;
        }

        $from = $purchase->getOriginal('status');
        $to   = $purchase->status;

        if ($to === 'completed' && $from !== 'completed') {
            $this->processPurchase($purchase);
        } elseif ($to === 'cancelled' && $from !== 'cancelled') {
            $this->cancelPurchase($purchase, wasCompleted: $from === 'completed');
        }
    }

    /**
     * Un achat validé ne se supprime pas (il faut l'annuler) : sinon stock et dette
     * fournisseur resteraient gonflés. Retourner false annule la suppression.
     */
    public function deleting(Purchase $purchase): bool
    {
        if ($purchase->status === 'completed') {
            Log::warning("Suppression refusée de l'achat validé {$purchase->reference} (utiliser l'annulation).");

            return false;
        }

        return true;
    }

    // ═══════════════════════════════════════════════
    // Validation
    // ═══════════════════════════════════════════════

    private function processPurchase(Purchase $purchase): void
    {
        $purchase->load('items.product', 'supplier');

        if ($purchase->items->isEmpty()) {
            Log::warning("Purchase {$purchase->reference} validé sans items");

            return;
        }

        DB::transaction(function () use ($purchase) {
            foreach ($purchase->items as $item) {
                if (! $item->product) {
                    Log::error("Item {$item->id} sans produit lors de la validation Purchase {$purchase->reference}");
                    continue;
                }

                // Anti fuite cross-tenant : jamais de mouvement sur le produit d'un autre shop
                if ($item->product->shop_id !== $purchase->shop_id) {
                    Log::error("Item {$item->id} : produit d'un autre commerce ignoré (Purchase {$purchase->reference})");
                    continue;
                }

                // Nombre d'unités de détail entrant en stock
                $convQty    = max(1, (float) ($item->conversion_qty ?? 1));
                $stockUnits = $item->quantity * $convQty;

                StockMovement::create([
                    'shop_id'    => $purchase->shop_id,
                    'product_id' => $item->product_id,
                    'user_id'    => $purchase->user_id,
                    'type'       => 'in',
                    'quantity'   => $stockUnits,
                    'reason'     => "Achat {$purchase->reference}" . ($convQty > 1 ? " ({$item->quantity} colis × {$convQty})" : ''),
                ]);

                // Prix d'achat par unité de détail (dernier prix connu)
                $item->product->update(['buy_price' => round($item->unit_cost / $convQty, 2)]);
            }

            if ($purchase->supplier_id && (float) $purchase->debt_amount > 0) {
                $purchase->supplier->increment('balance', $purchase->debt_amount);
            }
        });
    }

    // ═══════════════════════════════════════════════
    // Annulation
    // ═══════════════════════════════════════════════

    private function cancelPurchase(Purchase $purchase, bool $wasCompleted): void
    {
        DB::transaction(function () use ($purchase, $wasCompleted) {
            if ($wasCompleted) {
                $purchase->load('items', 'supplier');

                foreach ($purchase->items as $item) {
                    $convQty = max(1, (float) ($item->conversion_qty ?? 1));

                    StockMovement::create([
                        'shop_id'    => $purchase->shop_id,
                        'product_id' => $item->product_id,
                        'user_id'    => auth()->id() ?? $purchase->user_id,
                        'type'       => 'out',
                        'quantity'   => $item->quantity * $convQty,
                        'reason'     => "Annulation achat {$purchase->reference}",
                    ]);
                }

                // La dette restante avait été ajoutée au fournisseur à la validation
                if ($purchase->supplier_id && (float) $purchase->debt_amount > 0) {
                    $purchase->supplier->decrement('balance', $purchase->debt_amount);
                }
            }

            // Plus aucune dette sur un achat annulé — requête directe pour éviter la boucle observer
            Purchase::whereKey($purchase->id)->update([
                'debt_amount'    => 0,
                'payment_status' => (float) $purchase->paid_amount > 0 ? 'partial' : 'unpaid',
            ]);
        });
    }
}
