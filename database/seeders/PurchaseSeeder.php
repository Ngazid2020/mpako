<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Shop;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class PurchaseSeeder extends Seeder
{
    public function run(): void
    {
        $shop = Shop::where('slug', 'boutique-ali')->first();
        $user = User::where('email', 'ali@test.com')->first();

        if (!$shop || !$user) {
            $this->command->error('Lance d\'abord KomorShopSeeder');
            return;
        }

        // ── Fournisseurs ──
        $grossiste = Supplier::firstOrCreate(
            ['shop_id' => $shop->id, 'name' => 'Grossiste Moroni'],
            ['phone' => '333 10 00', 'address' => 'Volo Volo, Moroni']
        );

        $importateur = Supplier::firstOrCreate(
            ['shop_id' => $shop->id, 'name' => 'Importateur Anjouanais'],
            ['phone' => '333 20 00', 'address' => 'Port de Mutsamudu']
        );

        $this->command->info('✅ Fournisseurs créés');

        // ── Produits disponibles ──
        $products = $shop->products()->get();

        if ($products->isEmpty()) {
            $this->command->error('Lance d\'abord StockSeeder');
            return;
        }

        // ── Achat 1 : Validé (stock déjà mis à jour) ──
        $purchase1 = Purchase::create([
            'shop_id'      => $shop->id,
            'supplier_id'  => $grossiste->id,
            'user_id'      => $user->id,
            'reference'    => 'ACH-' . now()->format('Ymd') . '-0001',
            'status'       => 'completed',
            'total_amount' => 0,
            'paid_amount'  => 0,
            'debt_amount'  => 0,
            'purchased_at' => today()->subDays(3),
        ]);

        $items1 = [
            ['product' => $products->firstWhere('name', 'Coca-Cola 1.5L'), 'qty' => 24, 'cost' => 450],
            ['product' => $products->firstWhere('name', 'Sucre 1kg'),      'qty' => 20, 'cost' => 650],
        ];

        $total1 = 0;
        foreach ($items1 as $item) {
            if (!$item['product']) continue;

            $subtotal = $item['qty'] * $item['cost'];
            $total1  += $subtotal;

            PurchaseItem::create([
                'purchase_id'  => $purchase1->id,
                'product_id'   => $item['product']->id,
                'product_name' => $item['product']->name,
                'quantity'     => $item['qty'],
                'unit_cost'    => $item['cost'],
                'subtotal'     => $subtotal,
            ]);
        }

        $purchase1->update([
            'total_amount' => $total1,
            'paid_amount'  => $total1,
            'debt_amount'  => 0,
        ]);

        // ── Achat 2 : En attente (à valider) ──
        $purchase2 = Purchase::create([
            'shop_id'      => $shop->id,
            'supplier_id'  => $importateur->id,
            'user_id'      => $user->id,
            'reference'    => 'ACH-' . now()->format('Ymd') . '-0002',
            'status'       => 'pending',
            'total_amount' => 0,
            'paid_amount'  => 0,
            'debt_amount'  => 0,
            'purchased_at' => today(),
            'note'         => 'Livraison à confirmer',
        ]);

        $items2 = [
            ['product' => $products->firstWhere('name', 'Riz parfumé 5kg'),   'qty' => 10, 'cost' => 3200],
            ['product' => $products->firstWhere('name', 'Huile végétale 1L'), 'qty' => 12, 'cost' => 1100],
        ];

        $total2 = 0;
        foreach ($items2 as $item) {
            if (!$item['product']) continue;

            $subtotal = $item['qty'] * $item['cost'];
            $total2  += $subtotal;

            PurchaseItem::create([
                'purchase_id'  => $purchase2->id,
                'product_id'   => $item['product']->id,
                'product_name' => $item['product']->name,
                'quantity'     => $item['qty'],
                'unit_cost'    => $item['cost'],
                'subtotal'     => $subtotal,
            ]);
        }

        // Achat partiellement payé → dette
        $paid2 = 20000;
        $purchase2->update([
            'total_amount' => $total2,
            'paid_amount'  => $paid2,
            'debt_amount'  => max(0, $total2 - $paid2),
        ]);

        $this->command->info('✅ Achats de test créés');
        $this->command->table(
            ['Référence', 'Fournisseur', 'Total', 'Statut'],
            [
                [$purchase1->reference, $grossiste->name,    number_format($total1, 0) . ' KMF', '✅ Validé'],
                [$purchase2->reference, $importateur->name,  number_format($total2, 0) . ' KMF', '⏳ En attente'],
            ]
        );
    }
}