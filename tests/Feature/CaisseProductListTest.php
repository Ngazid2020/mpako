<?php

namespace Tests\Feature;

use App\Filament\Commerce\Pages\Caisse;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Concerns\BuildsShopFixtures;
use Tests\TestCase;

/**
 * Caisse : la liste des produits est affichée dès l'ouverture, la recherche ne fait que filtrer.
 */
class CaisseProductListTest extends TestCase
{
    use BuildsShopFixtures;
    use RefreshDatabase;

    public function test_products_are_listed_without_typing_and_search_filters_them(): void
    {
        $shop  = $this->makeShop();
        $other = $this->makeShop('Autre');
        $user  = $this->makeMember($shop, ['page_Caisse']);

        $riz   = $this->makeProduct($shop);
        $riz->update(['name' => 'Riz parfumé 5kg']);
        $huile = $this->makeProduct($shop);
        $huile->update(['name' => 'Huile végétale 1L']);
        $inactif = $this->makeProduct($shop);
        $inactif->update(['name' => 'Produit archivé', 'is_active' => false]);
        $etranger = $this->makeProduct($other);
        $etranger->update(['name' => 'Produit autre commerce']);

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('commerce'));
        Filament::setTenant($shop);

        // Sans rien taper : tous les produits actifs du commerce
        Livewire::test(Caisse::class)
            ->assertSee('Riz parfumé 5kg')
            ->assertSee('Huile végétale 1L')
            ->assertDontSee('Produit archivé')
            ->assertDontSee('Produit autre commerce')
            // Recherche : filtre
            ->set('search', 'riz')
            ->assertSee('Riz parfumé 5kg')
            ->assertDontSee('Huile végétale 1L')
            // Effacer la recherche : la liste complète revient
            ->set('search', '')
            ->assertSee('Huile végétale 1L');
    }
}
