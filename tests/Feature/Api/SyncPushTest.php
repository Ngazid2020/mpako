<?php

namespace Tests\Feature\Api;

use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Concerns\BuildsShopFixtures;
use Tests\TestCase;

/**
 * Sync offline (POST /push) : idempotence, isolation, permissions, achats.
 */
class SyncPushTest extends TestCase
{
    use BuildsShopFixtures;
    use RefreshDatabase;

    private function push($shop, array $operations)
    {
        return $this->postJson("/api/shop/{$shop->slug}/push", ['operations' => $operations]);
    }

    private function op(string $type, array $payload, string $localId): array
    {
        return ['type' => $type, 'local_id' => $localId, 'payload' => $payload, 'created_offline_at' => now()->toISOString()];
    }

    public function test_replayed_operation_is_not_applied_twice(): void
    {
        $shop    = $this->makeShop();
        $user    = $this->makeMember($shop, ['create_sale']);
        $product = $this->makeProduct($shop, stock: 10);
        Sanctum::actingAs($user);

        $op = $this->op('create_sale', ['items' => [['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 1000]]], 'sale-abc');

        $first  = $this->push($shop, [$op])->assertOk()->json('results.0');
        $second = $this->push($shop, [$op])->assertOk()->json('results.0');

        $this->assertSame('synced', $first['status']);
        $this->assertSame('synced', $second['status']);
        $this->assertSame($first['data']['id'], $second['data']['id']);
        $this->assertSame(1, Sale::count());
        $this->assertEquals(8, (float) $product->fresh()->stock_qty);
    }

    public function test_cross_tenant_stock_adjustment_fails(): void
    {
        $shopA    = $this->makeShop('A');
        $shopB    = $this->makeShop('B');
        $user     = $this->makeMember($shopA, ['create_stock::movement']);
        $productB = $this->makeProduct($shopB, stock: 50);
        Sanctum::actingAs($user);

        $result = $this->push($shopA, [
            $this->op('create_stock_adjustment', ['product_id' => $productB->id, 'type' => 'adjustment', 'quantity' => 0], 'adj-1'),
        ])->json('results.0');

        $this->assertSame('failed', $result['status']);
        $this->assertEquals(50, (float) $productB->fresh()->stock_qty);
    }

    public function test_operation_without_permission_fails(): void
    {
        $shop    = $this->makeShop();
        $user    = $this->makeMember($shop, ['create_sale']);
        $product = $this->makeProduct($shop, stock: 3);
        Sanctum::actingAs($user);

        $result = $this->push($shop, [
            $this->op('create_stock_adjustment', ['product_id' => $product->id, 'type' => 'adjustment', 'quantity' => 999], 'adj-2'),
        ])->json('results.0');

        $this->assertSame('failed', $result['status']);
        $this->assertEquals(3, (float) $product->fresh()->stock_qty);
    }

    public function test_purchase_pushed_as_completed_updates_stock_and_debt(): void
    {
        $shop     = $this->makeShop();
        $user     = $this->makeMember($shop, ['create_purchase', 'update_purchase']);
        $supplier = $this->makeSupplier($shop);
        $product  = $this->makeProduct($shop, stock: 1);
        Sanctum::actingAs($user);

        $result = $this->push($shop, [
            $this->op('create_purchase', [
                'supplier_id' => $supplier->id,
                'status'      => 'completed',
                'items'       => [['product_id' => $product->id, 'quantity' => 4, 'unit_cost' => 500]],
            ], 'pur-1'),
        ])->json('results.0');

        $this->assertSame('synced', $result['status'], json_encode($result));
        // Avant correctif : stock inchangé (observer sans lignes) et dette à 0
        $this->assertEquals(5, (float) $product->fresh()->stock_qty);
        $this->assertEquals(2000, (float) $supplier->fresh()->balance);
    }
}
