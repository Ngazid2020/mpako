<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsShopFixtures;
use Tests\TestCase;

/**
 * Smoke tests : les pages Filament principales se chargent sans erreur 500.
 */
class FilamentSmokeTest extends TestCase
{
    use BuildsShopFixtures;
    use RefreshDatabase;

    public function test_admin_pages_render(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'is_approved' => true, 'phone' => '+2693000001']);
        foreach (['view_any_shop', 'view_any_wallet', 'view_any_wallet::transaction', 'view_any_subscription', 'view_any_subscription::plan'] as $p) {
            \Spatie\Permission\Models\Permission::findOrCreate($p, 'web');
        }
        $admin->givePermissionTo(['view_any_shop', 'view_any_wallet', 'view_any_wallet::transaction', 'view_any_subscription', 'view_any_subscription::plan']);
        $shop  = $this->makeShop();
        Wallet::create(['shop_id' => $shop->id, 'balance' => 0, 'is_active' => true, 'status' => 'draft']);

        $this->actingAs($admin);

        foreach (['/admin/shops', '/admin/wallets', '/admin/wallet-transactions', '/admin/subscriptions', '/admin/subscription-plans'] as $url) {
            $response = $this->get($url);
            $this->assertContains($response->status(), [200, 302], "{$url} → {$response->status()}");
            $this->assertNotEquals(500, $response->status(), $url);
        }
    }

    public function test_commerce_pages_render_for_owner(): void
    {
        $shop  = $this->makeShop();
        $owner = $this->makeMember($shop, [
            'view_any_purchase', 'create_purchase', 'view_any_product', 'view_any_credit',
            'view_any_wallet', 'view_wallet', 'page_Caisse',
        ]);
        $wallet = Wallet::create(['shop_id' => $shop->id, 'balance' => 0, 'is_active' => true, 'status' => 'draft']);

        $this->actingAs($owner);

        $base = "/commerce/{$shop->slug}";
        foreach (["{$base}/purchases", "{$base}/purchases/create", "{$base}/products", "{$base}/credits", "{$base}/wallets", "{$base}/wallets/{$wallet->id}", "{$base}/caisse"] as $url) {
            $response = $this->get($url);
            $this->assertNotEquals(500, $response->status(), "{$url} → 500");
            $this->assertContains($response->status(), [200, 302, 403], "{$url} → {$response->status()}");
        }
    }
}
