<?php

namespace App\Filament\Commerce\Pages;

use App\Filament\Commerce\Resources\SaleResource;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class Caisse extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-shopping-cart';
    protected static ?string $navigationLabel = 'Caisse';
    protected static ?string $title           = '💰 Caisse';
    protected static ?int    $navigationSort  = 1;
    // Pas de groupe → en haut de la navigation
    protected static ?string $navigationGroup = null;

    protected static string $view = 'filament.commerce.pages.caisse';

    // ─────────────────────────────────────────────
    // PROPRIÉTÉS LIVEWIRE (état de la page)
    // ─────────────────────────────────────────────

    /** Terme de recherche produit */
    public string $search = '';

    /** Les produits trouvés par la recherche */
    public Collection $searchResults;

    /** Le panier : tableau de lignes */
    public array $cart = [];

    /** Montant payé par le client */
    public float $paidAmount = 0;

    /** Note sur la vente */
    public string $note = '';

    // ─────────────────────────────────────────────
    // INITIALISATION
    // ─────────────────────────────────────────────

    public function mount(): void
    {
        $this->searchResults = collect();
    }

    // ─────────────────────────────────────────────
// CALCULS
// ─────────────────────────────────────────────

    /**
     * Total du panier en KMF
     */
    public function getTotal(): float
    {
        return collect($this->cart)->sum('subtotal');
    }

    /**
     * Monnaie à rendre
     */
    public function getChange(): float
    {
        return max(0, $this->paidAmount - $this->getTotal());
    }

    /**
     * Nombre d'articles dans le panier
     */
    public function getCartCount(): int
    {
        return count($this->cart);
    }

    // ─────────────────────────────────────────────
    // RECHERCHE PRODUIT
    // ─────────────────────────────────────────────

    /**
     * Déclenché à chaque frappe dans le champ de recherche
     */
    public function updatedSearch(): void
    {
        if (strlen($this->search) < 1) {
            $this->searchResults = collect();
            return;
        }

        $shop = Filament::getTenant();

        $this->searchResults = $shop->products()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('barcode', $this->search); // Recherche par code-barres exact
            })
            ->with(['category', 'unit'])
            ->limit(8) // Max 8 résultats pour rester lisible
            ->get();
    }

    // ─────────────────────────────────────────────
    // GESTION DU PANIER
    // ─────────────────────────────────────────────

    /**
     * Ajouter un produit au panier
     */
    public function addToCart(int $productId): void
    {
        $product = Product::find($productId);

        if (!$product) return;

        // Vérifier le stock disponible
        if ($product->stock_qty <= 0) {
            Notification::make()
                ->title('Stock épuisé')
                ->body("{$product->name} est en rupture de stock.")
                ->danger()
                ->send();
            return;
        }

        // Si le produit est déjà dans le panier → incrémenter
        if (isset($this->cart[$productId])) {
            $newQty = $this->cart[$productId]['quantity'] + 1;

            // Vérifier qu'on ne dépasse pas le stock
            if ($newQty > $product->stock_qty) {
                Notification::make()
                    ->title('Stock insuffisant')
                    ->body("Il ne reste que {$product->stock_qty} {$product->unit?->abbreviation}.")
                    ->warning()
                    ->send();
                return;
            }

            $this->cart[$productId]['quantity'] = $newQty;
            $this->cart[$productId]['subtotal']  = $newQty * $this->cart[$productId]['unit_price'];
        } else {
            // Nouveau produit dans le panier
            $this->cart[$productId] = [
                'product_id'   => $product->id,
                'product_name' => $product->name,
                'unit_price'   => (float) $product->sell_price,
                'quantity'     => 1,
                'subtotal'     => (float) $product->sell_price,
                'stock_max'    => (float) $product->stock_qty,
                'unit'         => $product->unit?->abbreviation ?? 'pcs',
            ];
        }

        // Vider la recherche après ajout
        $this->search        = '';
        $this->searchResults = collect();
    }

    /**
     * Modifier la quantité d'une ligne du panier
     */
    public function updateQuantity(int $productId, float $quantity): void
    {
        if ($quantity <= 0) {
            $this->removeFromCart($productId);
            return;
        }

        $stockMax = $this->cart[$productId]['stock_max'] ?? 999;

        if ($quantity > $stockMax) {
            Notification::make()
                ->title('Stock insuffisant')
                ->body("Stock disponible : {$stockMax}")
                ->warning()
                ->send();
            $quantity = $stockMax;
        }

        $this->cart[$productId]['quantity'] = $quantity;
        $this->cart[$productId]['subtotal']  = $quantity * $this->cart[$productId]['unit_price'];
    }

    /**
     * Supprimer un produit du panier
     */
    public function removeFromCart(int $productId): void
    {
        unset($this->cart[$productId]);
    }

    /**
     * Vider tout le panier
     */
    public function clearCart(): void
    {
        $this->cart       = [];
        $this->paidAmount = 0;
        $this->note       = '';
    }

    // ─────────────────────────────────────────────
    // ENCAISSEMENT
    // ─────────────────────────────────────────────

    /**
     * Valider la vente
     */
    public function completeSale(): void
    {
        // ── Validations ──
        if (empty($this->cart)) {
            Notification::make()
                ->title('Panier vide')
                ->body('Ajoutez des produits avant de valider.')
                ->warning()
                ->send();
            return;
        }

        $total = $this->getTotal();

        if ($this->paidAmount < $total) {
            Notification::make()
                ->title('Montant insuffisant')
                ->body('Le montant payé est inférieur au total.')
                ->danger()
                ->send();
            return;
        }

        $shop = Filament::getTenant();

        // ── Créer la vente ──
        $sale = Sale::create([
            'shop_id'       => $shop->id,
            'user_id'       => auth()->id(),
            'reference'     => Sale::generateReference($shop->id),
            'status'        => 'completed',
            'total_amount'  => $total,
            'paid_amount'   => $this->paidAmount,
            'change_amount' => $this->getChange(),
            'note'          => $this->note,
        ]);

        // ── Créer les lignes et déduire le stock ──
        foreach ($this->cart as $item) {
            // Créer la ligne de vente
            SaleItem::create([
                'sale_id'      => $sale->id,
                'product_id'   => $item['product_id'],
                'product_name' => $item['product_name'],
                'quantity'     => $item['quantity'],
                'unit_price'   => $item['unit_price'],
                'subtotal'     => $item['subtotal'],
            ]);

            // Déduire du stock via un mouvement
            // L'Observer StockMovementObserver se charge du reste
            StockMovement::create([
                'shop_id'    => $shop->id,
                'product_id' => $item['product_id'],
                'user_id'    => auth()->id(),
                'type'       => 'out',
                'quantity'   => $item['quantity'],
                'reason'     => "Vente {$sale->reference}",
            ]);
        }

        // ── Notification de succès ──
        Notification::make()
            ->title('✅ Vente enregistrée !')
            ->body("Réf: {$sale->reference} | Monnaie: " . number_format($this->getChange(), 0, ',', ' ') . " KMF")
            ->success()
            ->duration(5000)
            ->send();

        // ── Réinitialiser la caisse ──
        $this->clearCart();
    }

    // ─────────────────────────────────────────────
    // ACTIONS FILAMENT (boutons de la page)
    // ─────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            Action::make('historique')
                ->label('Historique des ventes')
                ->icon('heroicon-o-clock')
                ->color('gray')
                ->url(fn() => SaleResource::getUrl('index')),
        ];
    }
}
