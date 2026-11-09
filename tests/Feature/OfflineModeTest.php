<?php

namespace Tests\Feature;

use App\Models\Credit;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Concerns\BuildsShopFixtures;
use Tests\TestCase;

/**
 * Mode hors ligne de la plateforme web : page autonome, token par poste,
 * opérations créées hors ligne et références entre elles (local_id).
 */
class OfflineModeTest extends TestCase
{
    use BuildsShopFixtures;
    use RefreshDatabase;

    private function op(string $type, array $payload, string $localId, int $order = 0): array
    {
        return ['type' => $type, 'local_id' => $localId, 'payload' => $payload, 'created_offline_at' => now()->addSeconds($order)->toISOString()];
    }

    private function push($shop, array $operations)
    {
        return $this->postJson("/api/shop/{$shop->slug}/push", ['operations' => $operations])->assertOk();
    }

    public function test_offline_page_requires_membership(): void
    {
        $shop  = $this->makeShop();
        $other = $this->makeShop('Autre');

        $this->get("/commerce/{$shop->slug}/hors-ligne")->assertRedirect('/commerce/login');

        $this->actingAs($this->makeMember($other, ['create_sale']));
        $this->get("/commerce/{$shop->slug}/hors-ligne")->assertForbidden();

        $this->actingAs($this->makeMember($shop, ['create_sale']));
        $this->get("/commerce/{$shop->slug}/hors-ligne")
            ->assertOk()
            ->assertSee('offlineApp', false)
            ->assertSee($shop->name);
    }

    public function test_token_is_issued_per_device_and_works_on_the_api(): void
    {
        $shop = $this->makeShop();
        $user = $this->makeMember($shop, ['view_any_product']);
        $this->actingAs($user);

        $first  = $this->postJson("/commerce/{$shop->slug}/hors-ligne/token", ['device_id' => 'poste-1'])->assertOk()->json();
        $second = $this->postJson("/commerce/{$shop->slug}/hors-ligne/token", ['device_id' => 'poste-1'])->assertOk()->json();
        $this->postJson("/commerce/{$shop->slug}/hors-ligne/token", ['device_id' => 'poste-2'])->assertOk();

        // Même poste → l'ancien token est révoqué ; autre poste → token distinct conservé
        $this->assertSame(2, $user->tokens()->count());
        $this->assertContains('view_any_product', $second['permissions']);

        $this->app['auth']->forgetGuards();
        $this->withToken($second['token'])->getJson("/api/shop/{$shop->slug}/pull?light=1")
            ->assertOk()
            ->assertJsonPath('sales', [])
            ->assertJsonStructure(['products', 'categories', 'units', 'synced_at']);

        $this->app['auth']->forgetGuards();
        $this->withToken($first['token'])->getJson("/api/shop/{$shop->slug}/pull")->assertUnauthorized();
    }

    public function test_customer_and_product_created_offline_can_be_used_in_the_same_batch(): void
    {
        $shop = $this->makeShop();
        $user = $this->makeMember($shop, ['create_sale', 'create_credit', 'create_customer', 'create_product']);
        Sanctum::actingAs($user);

        $results = $this->push($shop, [
            $this->op('create_customer', ['name' => 'Mariama'], 'cust-1', 0),
            $this->op('create_product', ['name' => 'Savon', 'sell_price' => 500, 'stock_qty' => 10], 'prod-1', 1),
            $this->op('create_sale', [
                'payment_type'      => 'credit',
                'customer_local_id' => 'cust-1',
                'items'             => [['product_local_id' => 'prod-1', 'quantity' => 4, 'unit_price' => 500]],
            ], 'sale-1', 2),
        ])->json('results');

        $this->assertSame(['synced', 'synced', 'synced'], array_column($results, 'status'), json_encode($results));

        $product = Product::where('name', 'Savon')->first();
        $this->assertEquals(6, (float) $product->stock_qty);
        $this->assertEquals(2000, (float) Credit::first()->customer->balance);
    }

    public function test_local_reference_resolves_across_batches_and_fails_cleanly_when_missing(): void
    {
        $shop     = $this->makeShop();
        $user     = $this->makeMember($shop, ['create_purchase', 'update_purchase', 'create_supplier']);
        $product  = $this->makeProduct($shop, stock: 0);
        Sanctum::actingAs($user);

        // Validation qui référence un achat inconnu → échec temporaire, rien n'est appliqué
        $failed = $this->push($shop, [$this->op('complete_purchase', ['purchase_local_id' => 'pur-x'], 'complete-x')])->json('results.0');
        $this->assertSame('failed', $failed['status']);
        $this->assertStringContainsString('pas encore synchronisé', $failed['error']);

        // Lot 1 : fournisseur + achat en attente, créés hors ligne
        $this->push($shop, [
            $this->op('create_supplier', ['name' => 'Grossiste Moroni'], 'sup-1', 0),
            $this->op('create_purchase', [
                'supplier_local_id' => 'sup-1',
                'items'             => [['product_id' => $product->id, 'quantity' => 3, 'unit_cost' => 1000]],
            ], 'pur-1', 1),
        ]);

        // Lot 2 (plus tard) : validation puis paiement via les local_id
        $results = $this->push($shop, [
            $this->op('complete_purchase', ['purchase_local_id' => 'pur-1'], 'complete-1', 0),
            $this->op('pay_purchase', ['purchase_local_id' => 'pur-1', 'amount' => 1000], 'pay-1', 1),
        ])->json('results');

        $this->assertSame(['synced', 'synced'], array_column($results, 'status'), json_encode($results));
        $this->assertEquals(3, (float) $product->fresh()->stock_qty);
        $this->assertEquals(2000, (float) $shop->suppliers()->first()->balance);
    }

    public function test_update_product_never_changes_stock_and_requires_permission(): void
    {
        $shop    = $this->makeShop();
        $product = $this->makeProduct($shop, stock: 7, sellPrice: 1000);

        Sanctum::actingAs($this->makeMember($shop, ['create_sale']));
        $denied = $this->push($shop, [$this->op('update_product', ['product_id' => $product->id, 'sell_price' => 1], 'upd-0')])->json('results.0');
        $this->assertSame('failed', $denied['status']);

        Sanctum::actingAs($this->makeMember($shop, ['update_product']));
        $this->push($shop, [$this->op('update_product', ['product_id' => $product->id, 'sell_price' => 1250, 'stock_qty' => 999], 'upd-1')]);

        $product->refresh();
        $this->assertEquals(1250, (float) $product->sell_price);
        $this->assertEquals(7, (float) $product->stock_qty);
    }
}
