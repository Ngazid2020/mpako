<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Concerns\BuildsShopFixtures;
use Tests\TestCase;

/**
 * API REST mobile : isolation multi-tenant, permissions, intégrité des ventes/achats/paiements.
 */
class CommerceApiTest extends TestCase
{
    use BuildsShopFixtures;
    use RefreshDatabase;

    private function url($shop, string $path): string
    {
        return "/api/shop/{$shop->slug}/{$path}";
    }

    public function test_sale_total_is_computed_server_side_and_stock_decrements(): void
    {
        $shop    = $this->makeShop();
        $user    = $this->makeMember($shop, ['create_sale']);
        $product = $this->makeProduct($shop, stock: 10, sellPrice: 1000);
        Sanctum::actingAs($user);

        $this->postJson($this->url($shop, 'sales'), [
            'items'        => [['product_id' => $product->id, 'product_name' => 'x', 'quantity' => 3, 'unit_price' => 1000]],
            'total_amount' => 1, // valeur client ignorée
            'paid_amount'  => 5000,
        ])->assertCreated()->assertJsonPath('total_amount', 3000);

        $this->assertEquals(7, (float) $product->fresh()->stock_qty);
    }

    public function test_sale_rejects_product_from_another_shop(): void
    {
        $shopA    = $this->makeShop('A');
        $shopB    = $this->makeShop('B');
        $user     = $this->makeMember($shopA, ['create_sale']);
        $productB = $this->makeProduct($shopB, stock: 10);
        Sanctum::actingAs($user);

        $this->postJson($this->url($shopA, 'sales'), [
            'items' => [['product_id' => $productB->id, 'quantity' => 1, 'unit_price' => 1]],
        ])->assertUnprocessable();

        $this->assertEquals(10, (float) $productB->fresh()->stock_qty);
    }

    public function test_sale_checks_aggregated_stock_across_lines(): void
    {
        $shop    = $this->makeShop();
        $user    = $this->makeMember($shop, ['create_sale']);
        $product = $this->makeProduct($shop, stock: 5);
        Sanctum::actingAs($user);

        $this->postJson($this->url($shop, 'sales'), [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3, 'unit_price' => 1000],
                ['product_id' => $product->id, 'quantity' => 3, 'unit_price' => 1000],
            ],
        ])->assertUnprocessable();

        $this->assertEquals(5, (float) $product->fresh()->stock_qty);
    }

    public function test_credit_sale_requires_a_customer_and_increases_balance(): void
    {
        $shop     = $this->makeShop();
        $user     = $this->makeMember($shop, ['create_sale', 'create_credit']);
        $product  = $this->makeProduct($shop, stock: 5, sellPrice: 1000);
        $customer = $this->makeCustomer($shop);
        Sanctum::actingAs($user);

        $payload = ['items' => [['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 1000]], 'payment_type' => 'credit'];

        $this->postJson($this->url($shop, 'sales'), $payload)->assertUnprocessable();

        $this->postJson($this->url($shop, 'sales'), $payload + ['customer_id' => $customer->id])->assertCreated();

        $this->assertEquals(2000, (float) $customer->fresh()->balance);
    }

    public function test_cashier_without_permission_cannot_create_purchase(): void
    {
        $shop    = $this->makeShop();
        $user    = $this->makeMember($shop, ['create_sale']);
        $product = $this->makeProduct($shop);
        Sanctum::actingAs($user);

        $this->postJson($this->url($shop, 'purchases'), [
            'items' => [['product_id' => $product->id, 'product_name' => 'x', 'quantity' => 1, 'unit_cost' => 10]],
        ])->assertForbidden();
    }

    public function test_purchase_create_complete_and_pay_flow(): void
    {
        $shop     = $this->makeShop();
        $user     = $this->makeMember($shop, ['create_purchase', 'update_purchase']);
        $supplier = $this->makeSupplier($shop);
        $product  = $this->makeProduct($shop, stock: 0);
        Sanctum::actingAs($user);

        $id = $this->postJson($this->url($shop, 'purchases'), [
            'supplier_id' => $supplier->id,
            'items'       => [['product_id' => $product->id, 'product_name' => 'x', 'quantity' => 4, 'unit_cost' => 250]],
        ])->assertCreated()->json('id');

        // Avant correctif : TypeError (le slug du shop arrivait dans $purchaseId)
        $this->postJson($this->url($shop, "purchases/{$id}/complete"))->assertOk();
        $this->assertEquals(4, (float) $product->fresh()->stock_qty);
        $this->assertEquals(1000, (float) $supplier->fresh()->balance);

        // Avant correctif : violation NOT NULL / dépassement
        $this->postJson($this->url($shop, "purchases/{$id}/pay"), ['amount' => 5000])->assertUnprocessable();
        $this->postJson($this->url($shop, "purchases/{$id}/pay"), ['amount' => 600])->assertOk()->assertJsonPath('status', 'partial');
        $this->assertEquals(400, (float) $supplier->fresh()->balance);
    }

    public function test_supplier_payment_is_spread_over_completed_purchases(): void
    {
        $shop     = $this->makeShop();
        $user     = $this->makeMember($shop, ['update_supplier']);
        $supplier = $this->makeSupplier($shop);
        $product  = $this->makeProduct($shop);

        $p1 = $this->makePendingPurchase($shop, $user, $supplier, $product, qty: 1, unitCost: 1000);
        $p2 = $this->makePendingPurchase($shop, $user, $supplier, $product, qty: 1, unitCost: 1000);
        $p1->update(['status' => 'completed']);
        $p2->update(['status' => 'completed']);
        Sanctum::actingAs($user);

        $this->postJson($this->url($shop, "suppliers/{$supplier->id}/pay"), ['amount' => 1500])->assertCreated();

        $this->assertEquals(500, (float) $supplier->fresh()->balance);
        $this->assertSame('paid', $p1->fresh()->payment_status);
        $this->assertSame('partial', $p2->fresh()->payment_status);
    }

    public function test_credit_repayment_works_and_is_scoped_to_shop(): void
    {
        $shopA    = $this->makeShop('A');
        $shopB    = $this->makeShop('B');
        $user     = $this->makeMember($shopA, ['create_sale', 'create_credit']);
        $customer = $this->makeCustomer($shopA);
        $product  = $this->makeProduct($shopA, stock: 5, sellPrice: 1000);
        Sanctum::actingAs($user);

        $this->postJson($this->url($shopA, 'sales'), [
            'items'        => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 1000]],
            'payment_type' => 'credit',
            'customer_id'  => $customer->id,
        ])->assertCreated();

        $creditId = $customer->credits()->first()->id;

        // Avant correctif : violation NOT NULL credit_payments.user_id
        $this->postJson($this->url($shopA, "credits/{$creditId}/pay"), ['amount' => 400])->assertOk();
        $this->assertEquals(600, (float) $customer->fresh()->balance);

        // Membre de A uniquement → pas d'accès au shop B
        $this->postJson($this->url($shopB, "credits/{$creditId}/pay"), ['amount' => 1])->assertForbidden();
    }

    public function test_unapproved_user_token_is_rejected(): void
    {
        $shop = $this->makeShop();
        $user = $this->makeMember($shop, ['view_any_product'], approved: false);
        Sanctum::actingAs($user);

        $this->getJson($this->url($shop, 'products'))->assertForbidden();
    }

    public function test_expense_without_description_no_longer_fails(): void
    {
        $shop = $this->makeShop();
        $user = $this->makeMember($shop, ['create_expense']);
        Sanctum::actingAs($user);

        $this->postJson($this->url($shop, 'expenses'), ['amount' => 1500])->assertCreated();
    }
}
