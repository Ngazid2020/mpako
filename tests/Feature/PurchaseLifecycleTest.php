<?php

namespace Tests\Feature;

use App\Models\Purchase;
use App\Models\SupplierPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsShopFixtures;
use Tests\TestCase;

/**
 * Cycle de vie d'un achat et cohérence stock / dette fournisseur (PurchaseObserver, SupplierPaymentObserver).
 */
class PurchaseLifecycleTest extends TestCase
{
    use BuildsShopFixtures;
    use RefreshDatabase;

    public function test_completing_a_purchase_adds_stock_and_supplier_debt(): void
    {
        $shop     = $this->makeShop();
        $user     = $this->makeMember($shop);
        $supplier = $this->makeSupplier($shop);
        $product  = $this->makeProduct($shop, stock: 2);
        $purchase = $this->makePendingPurchase($shop, $user, $supplier, $product, qty: 5, unitCost: 400);

        $purchase->update(['status' => 'completed']);

        $this->assertEquals(7, (float) $product->fresh()->stock_qty);
        $this->assertEquals(400, (float) $product->fresh()->buy_price);
        $this->assertEquals(2000, (float) $supplier->fresh()->balance);
        $this->assertSame('unpaid', $purchase->fresh()->payment_status);
    }

    public function test_cancelling_a_pending_purchase_does_not_touch_supplier_balance(): void
    {
        $shop     = $this->makeShop();
        $user     = $this->makeMember($shop);
        $supplier = $this->makeSupplier($shop);
        $product  = $this->makeProduct($shop, stock: 2);
        $purchase = $this->makePendingPurchase($shop, $user, $supplier, $product);

        $purchase->update(['status' => 'cancelled']);

        // Avant correctif : balance = -2000 (dette jamais comptée mais retirée)
        $this->assertEquals(0, (float) $supplier->fresh()->balance);
        $this->assertEquals(2, (float) $product->fresh()->stock_qty);
        $this->assertEquals(0, (float) $purchase->fresh()->debt_amount);
    }

    public function test_cancelling_a_completed_partially_paid_purchase_reverses_stock_and_remaining_debt(): void
    {
        $shop     = $this->makeShop();
        $user     = $this->makeMember($shop);
        $supplier = $this->makeSupplier($shop);
        $product  = $this->makeProduct($shop, stock: 2);
        $purchase = $this->makePendingPurchase($shop, $user, $supplier, $product, qty: 5, unitCost: 400);

        $purchase->update(['status' => 'completed']);
        SupplierPayment::create(['purchase_id' => $purchase->id, 'user_id' => $user->id, 'amount' => 500, 'paid_at' => today()]);

        $this->assertEquals(1500, (float) $supplier->fresh()->balance);

        $purchase->fresh()->update(['status' => 'cancelled']);

        $this->assertEquals(2, (float) $product->fresh()->stock_qty);
        $this->assertEquals(0, (float) $supplier->fresh()->balance);
    }

    public function test_supplier_payment_is_capped_and_its_deletion_reverses_exactly(): void
    {
        $shop     = $this->makeShop();
        $user     = $this->makeMember($shop);
        $supplier = $this->makeSupplier($shop);
        $product  = $this->makeProduct($shop);
        $purchase = $this->makePendingPurchase($shop, $user, $supplier, $product, qty: 5, unitCost: 400);
        $purchase->update(['status' => 'completed']);

        // Trop-perçu : 5000 pour une dette de 2000
        $payment = SupplierPayment::create(['purchase_id' => $purchase->id, 'user_id' => $user->id, 'amount' => 5000, 'paid_at' => today()]);

        $this->assertEquals(2000, (float) $payment->amount);
        $this->assertEquals(0, (float) $supplier->fresh()->balance);
        $this->assertSame('paid', $purchase->fresh()->payment_status);

        $payment->delete();

        // Avant correctif : balance = 5000 au lieu de 2000
        $this->assertEquals(2000, (float) $supplier->fresh()->balance);
        $this->assertSame('unpaid', $purchase->fresh()->payment_status);
    }

    public function test_paying_a_pending_purchase_is_refused(): void
    {
        $shop     = $this->makeShop();
        $user     = $this->makeMember($shop);
        $supplier = $this->makeSupplier($shop);
        $purchase = $this->makePendingPurchase($shop, $user, $supplier, $this->makeProduct($shop));

        $this->expectException(\RuntimeException::class);

        SupplierPayment::create(['purchase_id' => $purchase->id, 'user_id' => $user->id, 'amount' => 100, 'paid_at' => today()]);
    }

    public function test_a_completed_purchase_cannot_be_deleted(): void
    {
        $shop     = $this->makeShop();
        $user     = $this->makeMember($shop);
        $purchase = $this->makePendingPurchase($shop, $user, $this->makeSupplier($shop), $this->makeProduct($shop));
        $purchase->update(['status' => 'completed']);

        $purchase->fresh()->delete();

        $this->assertNotNull(Purchase::find($purchase->id));
    }

    public function test_stock_movement_cannot_target_another_shops_product(): void
    {
        $shopA    = $this->makeShop('A');
        $shopB    = $this->makeShop('B');
        $productB = $this->makeProduct($shopB, stock: 50);

        $this->expectException(\InvalidArgumentException::class);

        \App\Models\StockMovement::create([
            'shop_id'    => $shopA->id,
            'product_id' => $productB->id,
            'type'       => 'adjustment',
            'quantity'   => 0,
        ]);
    }
}
