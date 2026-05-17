<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class StockSeeder extends Seeder
{
    public function run(): void
    {
        $shop = Shop::where('slug', 'boutique-ali')->first();

        if (!$shop) {
            $this->command->error('Lance d\'abord KomorShopSeeder');
            return;
        }

        // ── Unités ──
        $piece   = Unit::firstOrCreate(['shop_id' => $shop->id, 'name' => 'Pièce'],
            ['abbreviation' => 'pcs']);
        $kilo    = Unit::firstOrCreate(['shop_id' => $shop->id, 'name' => 'Kilogramme'],
            ['abbreviation' => 'kg']);
        $litre   = Unit::firstOrCreate(['shop_id' => $shop->id, 'name' => 'Litre'],
            ['abbreviation' => 'L']);
        $sachet  = Unit::firstOrCreate(['shop_id' => $shop->id, 'name' => 'Sachet'],
            ['abbreviation' => 'sct']);
        $carton  = Unit::firstOrCreate(['shop_id' => $shop->id, 'name' => 'Carton'],
            ['abbreviation' => 'ctn']);

        // ── Catégories ──
        $boissons    = Category::firstOrCreate(['shop_id' => $shop->id, 'name' => 'Boissons'],
            ['color' => '#3b82f6']);
        $alimentaire = Category::firstOrCreate(['shop_id' => $shop->id, 'name' => 'Alimentaire'],
            ['color' => '#f59e0b']);
        $hygiene     = Category::firstOrCreate(['shop_id' => $shop->id, 'name' => 'Hygiène'],
            ['color' => '#10b981']);

        // ── Produits ──
        $products = [
            [
                'name'        => 'Coca-Cola 1.5L',
                'category_id' => $boissons->id,
                'unit_id'     => $piece->id,
                'buy_price'   => 500,
                'sell_price'  => 700,
                'stock_qty'   => 24,
                'stock_alert' => 6,
            ],
            [
                'name'        => 'Riz parfumé 5kg',
                'category_id' => $alimentaire->id,
                'unit_id'     => $kilo->id,
                'buy_price'   => 3500,
                'sell_price'  => 4500,
                'stock_qty'   => 50,
                'stock_alert' => 10,
            ],
            [
                'name'        => 'Sucre 1kg',
                'category_id' => $alimentaire->id,
                'unit_id'     => $kilo->id,
                'buy_price'   => 700,
                'sell_price'  => 900,
                'stock_qty'   => 3,       // ← stock bas pour tester l'alerte
                'stock_alert' => 5,
            ],
            [
                'name'        => 'Huile végétale 1L',
                'category_id' => $alimentaire->id,
                'unit_id'     => $litre->id,
                'buy_price'   => 1200,
                'sell_price'  => 1500,
                'stock_qty'   => 0,       // ← rupture pour tester
                'stock_alert' => 3,
            ],
            [
                'name'        => 'Savon Palmolive',
                'category_id' => $hygiene->id,
                'unit_id'     => $piece->id,
                'buy_price'   => 300,
                'sell_price'  => 450,
                'stock_qty'   => 30,
                'stock_alert' => 5,
            ],
        ];

        foreach ($products as $data) {
            Product::firstOrCreate(
                ['shop_id' => $shop->id, 'name' => $data['name']],
                array_merge($data, ['shop_id' => $shop->id])
            );
        }

        $this->command->info('✅ Stock de test créé pour Boutique Ali');
        $this->command->table(
            ['Produit', 'Stock', 'Prix vente'],
            collect($products)->map(fn ($p) => [
                $p['name'],
                $p['stock_qty'],
                number_format($p['sell_price'], 0, ',', ' ') . ' KMF',
            ])->toArray()
        );
    }
}