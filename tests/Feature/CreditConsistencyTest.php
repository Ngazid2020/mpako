<?php

namespace Tests\Feature;

use App\Models\Credit;
use App\Models\CreditPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsShopFixtures;
use Tests\TestCase;

/**
 * Cohérence crédit client ↔ customer.balance (CreditObserver, CreditPaymentObserver).
 */
class CreditConsistencyTest extends TestCase
{
    use BuildsShopFixtures;
    use RefreshDatabase;

    private function makeCredit($shop, $user, $customer, float $total): Credit
    {
        return Credit::create([
            'shop_id'          => $shop->id,
            'customer_id'      => $customer->id,
            'user_id'          => $user->id,
            'reference'        => Credit::generateReference($shop->id),
            'status'           => 'pending',
            'total_amount'     => $total,
            'paid_amount'      => 0,
            'remaining_amount' => $total,
            'description'      => 'Test',
        ]);
    }

    public function test_overpayment_is_capped_and_deletion_restores_the_real_debt(): void
    {
        $shop     = $this->makeShop();
        $user     = $this->makeMember($shop);
        $customer = $this->makeCustomer($shop);
        $credit   = $this->makeCredit($shop, $user, $customer, 3500);

        $payment = CreditPayment::create(['credit_id' => $credit->id, 'user_id' => $user->id, 'amount' => 4500, 'paid_at' => today()]);

        $this->assertEquals(3500, (float) $payment->amount);
        $this->assertEquals(0, (float) $customer->fresh()->balance);
        $this->assertSame('paid', $credit->fresh()->status);

        $payment->delete();

        // Avant correctif : 4500
        $this->assertEquals(3500, (float) $customer->fresh()->balance);
        $this->assertSame('pending', $credit->fresh()->status);
    }

    public function test_editing_credit_total_adjusts_customer_balance(): void
    {
        $shop     = $this->makeShop();
        $user     = $this->makeMember($shop);
        $customer = $this->makeCustomer($shop);
        $credit   = $this->makeCredit($shop, $user, $customer, 5000);

        CreditPayment::create(['credit_id' => $credit->id, 'user_id' => $user->id, 'amount' => 1000, 'paid_at' => today()]);
        $this->assertEquals(4000, (float) $customer->fresh()->balance);

        $credit->fresh()->update(['total_amount' => 3000]);

        $credit->refresh();
        $this->assertEquals(2000, (float) $credit->remaining_amount);
        $this->assertSame('partial', $credit->status);
        $this->assertEquals(2000, (float) $customer->fresh()->balance);
    }

    public function test_paying_an_already_settled_credit_is_refused(): void
    {
        $shop     = $this->makeShop();
        $user     = $this->makeMember($shop);
        $customer = $this->makeCustomer($shop);
        $credit   = $this->makeCredit($shop, $user, $customer, 1000);

        CreditPayment::create(['credit_id' => $credit->id, 'user_id' => $user->id, 'amount' => 1000, 'paid_at' => today()]);

        $this->expectException(\RuntimeException::class);
        CreditPayment::create(['credit_id' => $credit->id, 'user_id' => $user->id, 'amount' => 10, 'paid_at' => today()]);
    }
}
