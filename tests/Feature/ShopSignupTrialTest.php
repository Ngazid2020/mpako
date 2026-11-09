<?php

namespace Tests\Feature;

use App\Filament\Commerce\Pages\Auth\Register;
use App\Models\Shop;
use App\Models\User;
use App\Services\ShopOnboarding;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Feature\Concerns\BuildsShopFixtures;
use Tests\TestCase;

/**
 * « Commencer » → inscription en libre-service : compte + boutique + essai gratuit d'un mois.
 */
class ShopSignupTrialTest extends TestCase
{
    use BuildsShopFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $permission = Permission::findOrCreate('view_any_purchase', 'web');
        Role::findOrCreate('owner', 'web')->givePermissionTo($permission);
    }

    public function test_commencer_creates_account_shop_and_one_month_trial(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 9)->setTime(10, 0));

        Filament::setCurrentPanel(Filament::getPanel('commerce'));

        Livewire::test(Register::class)
            ->set('data.name', 'Mariama Said')
            ->set('data.phone', '+2693214567')
            ->set('data.password', 'secret-123')
            ->set('data.passwordConfirmation', 'secret-123')
            ->set('data.shop_name', 'Boutique Mariama')
            ->set('data.shop_island', 'Grande Comore')
            ->set('data.shop_city', 'Moroni')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect();

        $user = User::where('phone', '+2693214567')->firstOrFail();
        $shop = Shop::where('name', 'Boutique Mariama')->firstOrFail();

        // Compte actif, connecté, propriétaire de sa boutique
        $this->assertTrue($user->is_approved);
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->shops()->whereKey($shop->id)->exists());
        $this->assertTrue($user->hasRole('owner'));
        $this->assertSame('boutique-mariama', $shop->slug);

        // Portefeuille + essai d'un mois
        $this->assertNotNull($shop->wallet);
        $subscription = $shop->currentSubscription();
        $this->assertTrue($subscription->plan->is_trial);
        $this->assertEquals(0, (float) $subscription->plan->price);
        $this->assertSame('2026-12-09', $subscription->ends_at->toDateString());
        $this->assertSame('2026-12-09', $shop->subscription_expires_at->toDateString());
        $this->assertTrue($shop->isOnTrial());
        $this->assertTrue($shop->canUseSystem());

        // Le bandeau d'essai est visible dans le panel
        $this->get("/commerce/{$shop->slug}/purchases")->assertOk()->assertSee('Essai gratuit');
    }

    public function test_commencer_page_is_reachable_by_guests(): void
    {
        $this->get('/')->assertOk()->assertSee('/commerce/register', false);

        $this->get('/commerce/register')
            ->assertOk()
            ->assertSee('Créez votre boutique')
            ->assertSee('essai gratuit</strong>', false);
    }

    public function test_trial_is_granted_only_once_per_user(): void
    {
        $user = User::factory()->create(['phone' => '+2693000002', 'is_approved' => true]);
        $onboarding = app(ShopOnboarding::class);

        $first  = $onboarding->createShop($user, ['name' => 'Première', 'island' => 'Anjouan', 'city' => 'Mutsamudu']);
        $second = $onboarding->createShop($user->fresh(), ['name' => 'Deuxième', 'island' => 'Anjouan', 'city' => 'Mutsamudu']);

        $this->assertTrue($first->isOnTrial());
        $this->assertFalse($second->isOnTrial());
        $this->assertNull($second->subscription_expires_at);
        $this->assertNotNull($second->wallet);
    }

    public function test_slug_is_unique_and_existing_employee_keeps_its_role(): void
    {
        Role::findOrCreate('cashier', 'web');
        $shopA   = $this->makeShop('Boutique Ali');
        $cashier = $this->makeMember($shopA);
        $cashier->assignRole('cashier');

        $shop = app(ShopOnboarding::class)->createShop($cashier, ['name' => $shopA->name, 'island' => 'Mohéli', 'city' => 'Fomboni']);

        $this->assertNotSame($shopA->slug, $shop->slug);
        // Rôles globaux : on n'élève pas un employé existant en « owner » (cf. US-101)
        $this->assertFalse($cashier->fresh()->hasRole('owner'));
    }
}
