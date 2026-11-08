<?php

namespace Tests\Feature\Concerns;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Shop;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Fabrique de données de test : commerce, membres avec permissions Shield, produits…
 */
trait BuildsShopFixtures
{
    protected function makeShop(string $name = 'Boutique Test'): Shop
    {
        return Shop::create([
            'name'      => $name,
            'slug'      => Str::slug($name) . '-' . Str::random(5),
            'island'    => 'Grande Comore',
            'currency'  => 'KMF',
            'is_active' => true,
        ]);
    }

    /**
     * Membre approuvé du commerce, avec les permissions données (noms Shield).
     */
    protected function makeMember(Shop $shop, array $permissions = [], bool $approved = true): User
    {
        $user = User::factory()->create([
            'phone'       => '+269' . random_int(3000000, 3999999),
            'is_approved' => $approved,
        ]);

        $user->shops()->attach($shop->id);

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->givePermissionTo($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user->fresh();
    }

    protected function makeProduct(Shop $shop, float $stock = 10, float $sellPrice = 1000, float $alert = 0): Product
    {
        return Product::create([
            'shop_id'     => $shop->id,
            'name'        => 'Produit ' . Str::random(4),
            'buy_price'   => 500,
            'sell_price'  => $sellPrice,
            'stock_qty'   => $stock,
            'stock_alert' => $alert,
            'is_active'   => true,
        ]);
    }

    protected function makeCustomer(Shop $shop): Customer
    {
        return Customer::create(['shop_id' => $shop->id, 'name' => 'Client ' . Str::random(4), 'balance' => 0, 'is_active' => true]);
    }

    protected function makeSupplier(Shop $shop): Supplier
    {
        return Supplier::create(['shop_id' => $shop->id, 'name' => 'Fournisseur ' . Str::random(4), 'balance' => 0, 'is_active' => true]);
    }

    /**
     * Achat 'pending' avec ses lignes (comme le font CreatePurchase / l'API).
     */
    protected function makePendingPurchase(Shop $shop, User $user, ?Supplier $supplier, Product $product, float $qty = 5, float $unitCost = 400): Purchase
    {
        $total = $qty * $unitCost;

        $purchase = Purchase::create([
            'shop_id'      => $shop->id,
            'user_id'      => $user->id,
            'supplier_id'  => $supplier?->id,
            'reference'    => Purchase::generateReference($shop->id),
            'status'       => 'pending',
            'total_amount' => $total,
            'paid_amount'  => 0,
            'debt_amount'  => $total,
            'purchased_at' => today(),
        ]);

        PurchaseItem::create([
            'purchase_id'  => $purchase->id,
            'product_id'   => $product->id,
            'product_name' => $product->name,
            'quantity'     => $qty,
            'unit_cost'    => $unitCost,
        ]);

        return $purchase->fresh();
    }
}
