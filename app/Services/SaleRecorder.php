<?php

namespace App\Services;

use App\Models\Credit;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Shop;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Enregistre une vente (API REST et sync offline) de façon cohérente :
 *  - produits et client obligatoirement du shop courant (anti cross-tenant)
 *  - contrôle de stock agrégé par produit (mode strict)
 *  - totaux recalculés côté serveur à partir des lignes (le client n'est pas cru)
 *  - vente à crédit ⇒ client obligatoire + création du Credit (CreditObserver → balance)
 *
 * Doit être appelé dans une transaction (il l'ouvre lui-même sinon).
 */
class SaleRecorder
{
    /**
     * @param  array  $data  items[], payment_type, customer_id, paid_amount, change_amount,
     *                       discount_amount (remise globale), note, credit{due_date,note}
     * @param  bool   $strictStock  true = refuse si stock insuffisant (REST) ;
     *                              false = la vente a déjà eu lieu hors-ligne, on l'enregistre.
     */
    public function record(Shop $shop, User $user, array $data, bool $strictStock = true, string $reasonSuffix = ''): Sale
    {
        return DB::transaction(function () use ($shop, $user, $data, $strictStock, $reasonSuffix) {
            $items = collect($data['items'] ?? []);

            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'La vente doit contenir au moins un article.']);
            }

            $paymentType = $data['payment_type'] ?? 'cash';

            if (! in_array($paymentType, ['cash', 'credit'], true)) {
                throw ValidationException::withMessages(['payment_type' => 'Type de paiement invalide.']);
            }

            // ── Client : doit appartenir au shop ──
            $customer = null;
            if (! empty($data['customer_id'])) {
                $customer = $shop->customers()->find($data['customer_id']);
                if (! $customer) {
                    throw ValidationException::withMessages(['customer_id' => 'Client introuvable dans ce commerce.']);
                }
            }

            if ($paymentType === 'credit' && ! $customer) {
                throw ValidationException::withMessages(['customer_id' => 'Un client est obligatoire pour une vente à crédit.']);
            }

            // ── Produits : tous du shop, verrouillés ──
            $productIds = $items->pluck('product_id')->map(fn ($id) => (int) $id)->unique();
            $products   = $shop->products()->whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');

            if ($products->count() !== $productIds->count()) {
                throw ValidationException::withMessages(['items' => 'Un ou plusieurs produits sont introuvables dans ce commerce.']);
            }

            // ── Stock agrégé par produit (une même référence peut apparaître sur 2 lignes) ──
            if ($strictStock) {
                $qtyByProduct = $items->groupBy(fn ($i) => (int) $i['product_id'])
                    ->map(fn ($lines) => $lines->sum(fn ($l) => (float) $l['quantity']));

                foreach ($qtyByProduct as $productId => $qty) {
                    $product = $products[$productId];
                    if ((float) $product->stock_qty < $qty) {
                        throw ValidationException::withMessages([
                            'items' => "Stock insuffisant pour « {$product->name} » (disponible : " . (float) $product->stock_qty . ').',
                        ]);
                    }
                }
            }

            $sale = Sale::create([
                'shop_id'         => $shop->id,
                'user_id'         => $user->id,
                'customer_id'     => $customer?->id,
                'payment_type'    => $paymentType,
                'reference'       => Sale::generateReference($shop->id),
                'status'          => 'completed',
                'total_amount'    => 0, // recalculé ci-dessous
                'paid_amount'     => 0,
                'change_amount'   => 0,
                'discount_amount' => 0,
                'note'            => $data['note'] ?? null,
            ]);

            $itemsTotal    = 0.0;
            $lineDiscounts = 0.0;

            foreach ($items as $item) {
                $product = $products[(int) $item['product_id']];

                $discountType  = in_array($item['discount_type'] ?? null, ['percent', 'fixed'], true) ? $item['discount_type'] : null;
                $discountValue = (float) ($item['discount_value'] ?? 0);
                if ($discountType === 'percent') {
                    $discountValue = min(100, max(0, $discountValue));
                }

                // SaleItem::saving recalcule discount_amount et subtotal
                $saleItem = SaleItem::create([
                    'sale_id'        => $sale->id,
                    'product_id'     => $product->id,
                    'product_name'   => $item['product_name'] ?? $product->name,
                    'quantity'       => $item['quantity'],
                    'unit_price'     => $item['unit_price'] ?? $product->sell_price,
                    'discount_type'  => $discountType,
                    'discount_value' => $discountValue,
                ]);

                $itemsTotal    += (float) $saleItem->subtotal;
                $lineDiscounts += (float) $saleItem->discount_amount;

                // StockMovementObserver met à jour product.stock_qty
                StockMovement::create([
                    'shop_id'    => $shop->id,
                    'product_id' => $product->id,
                    'user_id'    => $user->id,
                    'type'       => 'out',
                    'quantity'   => $item['quantity'],
                    'reason'     => trim("Vente {$sale->reference} {$reasonSuffix}"),
                ]);
            }

            $globalDiscount = min(max(0, (float) ($data['discount_amount'] ?? 0)), $itemsTotal);
            $total          = round($itemsTotal - $globalDiscount, 2);

            if ($paymentType === 'credit') {
                $paid   = 0;
                $change = 0;
            } else {
                $paid   = isset($data['paid_amount']) ? (float) $data['paid_amount'] : $total;
                $change = max(0, round($paid - $total, 2));
            }

            $sale->update([
                'total_amount'    => $total,
                'paid_amount'     => $paid,
                'change_amount'   => $change,
                'discount_amount' => round($lineDiscounts + $globalDiscount, 2),
            ]);

            if ($paymentType === 'credit') {
                Credit::create([
                    'shop_id'          => $shop->id,
                    'sale_id'          => $sale->id,
                    'customer_id'      => $customer->id,
                    'user_id'          => $user->id,
                    'reference'        => Credit::generateReference($shop->id),
                    'status'           => 'pending',
                    'total_amount'     => $total,
                    'paid_amount'      => 0,
                    'remaining_amount' => $total,
                    'due_date'         => $data['credit']['due_date'] ?? null,
                    'description'      => trim("Vente à crédit {$sale->reference} {$reasonSuffix}"),
                    'note'             => $data['credit']['note'] ?? null,
                ]);
                // CreditObserver augmente customer.balance
            }

            return $sale;
        });
    }
}
