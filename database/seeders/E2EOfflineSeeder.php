<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use App\Services\ShopOnboarding;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Données du test navigateur `npm run test:e2e` (CI).
 * Commerçant +2693999999 / secret123, boutique « boutique-test », produit « Riz 5kg ».
 *
 *   php artisan db:seed --class=E2EOfflineSeeder
 */
class E2EOfflineSeeder extends Seeder
{
    private const PERMISSIONS = [
        'view_any_product', 'create_product', 'update_product',
        'view_any_sale', 'create_sale',
        'view_any_credit', 'create_credit', 'update_credit',
        'view_any_customer', 'create_customer',
        'view_any_purchase', 'create_purchase', 'update_purchase',
        'view_any_supplier', 'create_supplier', 'update_supplier',
        'view_any_expense', 'create_expense',
        'view_any_stock::movement', 'create_stock::movement',
        'page_Caisse',
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('E2EOfflineSeeder est réservé aux environnements de test.');

            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        Role::findOrCreate(ShopOnboarding::OWNER_ROLE, 'web')->syncPermissions(self::PERMISSIONS);

        $user = User::firstOrCreate(
            ['phone' => '+2693999999'],
            ['name' => 'Test Hors Ligne', 'password' => bcrypt('secret123'), 'is_approved' => true],
        );

        $shop = $user->shops()->where('slug', 'boutique-test')->first()
            ?? app(ShopOnboarding::class)->createShop($user, [
                'name'   => 'Boutique Test',
                'slug'   => 'boutique-test',
                'island' => 'Grande Comore',
                'city'   => 'Moroni',
            ]);

        Product::firstOrCreate(
            ['shop_id' => $shop->id, 'name' => 'Riz 5kg'],
            ['sell_price' => 3500, 'buy_price' => 3000, 'stock_qty' => 20, 'stock_alert' => 2, 'is_active' => true],
        );

        $this->command?->info("E2E : {$user->phone} / secret123 → /commerce/{$shop->slug}");
    }
}
