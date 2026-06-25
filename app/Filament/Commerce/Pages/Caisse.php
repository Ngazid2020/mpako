<?php

namespace App\Filament\Commerce\Pages;

use App\Filament\Commerce\Resources\SaleResource;
use App\Models\Credit;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Traits\HasShieldPermissionPages;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Caisse extends Page
{
    use HasShieldPermissionPages;
    protected static ?string $navigationIcon  = 'heroicon-o-shopping-cart';
    protected static ?string $navigationLabel = 'Boutique';
    protected static ?string $title           = '💰 Boutique';
    protected static ?int    $navigationSort  = 1;
    // Pas de groupe → en haut de la navigation
    protected static ?string $navigationGroup = null;

    protected static string $view = 'filament.commerce.pages.caisse';


    // Modal crédit
    public bool $showCreditModal = false;

    // Client sélectionné
    public ?int $selectedCustomerId = null;

    // Échéance optionnelle
    public ?string $creditDueDate = null;

    // Note crédit
    public string $creditNote = '';

    // Reçu de vente
    public array $lastSale        = [];
    public bool  $showReceiptModal = false;
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
                    ->orWhere('barcode', $this->search);
            })
            ->with(['category', 'unit'])
            ->limit(8)
            ->get();

        // Auto-add si correspondance exacte de code-barres (scan caméra)
        $first = $this->searchResults->first();
        if ($this->searchResults->count() === 1 && $first?->barcode === $this->search) {
            $this->addToCart($first->id);
        }
    }

    // ─────────────────────────────────────────────
    // GESTION DU PANIER
    // ─────────────────────────────────────────────

    /**
     * Ajouter un produit au panier
     */
    public function addToCart(int $productId): void
    {
        $shop    = Filament::getTenant();
        $product = $shop->products()
            ->where('is_active', true)
            ->find($productId);

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

    public function closeReceipt(): void
    {
        $this->lastSale        = [];
        $this->showReceiptModal = false;
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

        $total  = $this->getTotal();
        $change = $this->getChange();

        if ($this->paidAmount < $total) {
            Notification::make()
                ->title('Montant insuffisant')
                ->body('Le montant payé est inférieur au total.')
                ->danger()
                ->send();
            return;
        }

        $shop = Filament::getTenant();

        try {
            $sale = DB::transaction(function () use ($shop, $total) {
                // Vérification du stock en temps réel avec verrou pessimiste
                foreach ($this->cart as $item) {
                    $product = $shop->products()
                        ->lockForUpdate()
                        ->find($item['product_id']);

                    if (!$product || $product->stock_qty < $item['quantity']) {
                        throw new \RuntimeException("Stock insuffisant pour « {$item['product_name']} ».");
                    }
                }

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

                foreach ($this->cart as $item) {
                    SaleItem::create([
                        'sale_id'      => $sale->id,
                        'product_id'   => $item['product_id'],
                        'product_name' => $item['product_name'],
                        'quantity'     => $item['quantity'],
                        'unit_price'   => $item['unit_price'],
                        'subtotal'     => $item['subtotal'],
                    ]);

                    StockMovement::create([
                        'shop_id'    => $shop->id,
                        'product_id' => $item['product_id'],
                        'user_id'    => auth()->id(),
                        'type'       => 'out',
                        'quantity'   => $item['quantity'],
                        'reason'     => "Vente {$sale->reference}",
                    ]);
                }

                return $sale;
            });

            Notification::make()
                ->title('✅ Vente enregistrée !')
                ->body("Réf: {$sale->reference} | Monnaie: " . number_format($change, 0, ',', ' ') . " KMF")
                ->success()
                ->duration(5000)
                ->send();

            $cartSnapshot = $this->cart;
            $paidPrev     = $this->paidAmount;
            $notePrev     = $this->note;

            $this->clearCart();

            $this->lastSale = [
                'reference' => $sale->reference,
                'items'     => collect($cartSnapshot)->values()->toArray(),
                'total'     => (int) $total,
                'paid'      => (int) $paidPrev,
                'change'    => (int) max(0, $paidPrev - $total),
                'note'      => $notePrev,
                'type'      => 'comptant',
                'customer'  => null,
            ];
            $this->showReceiptModal = true;

            $this->notifyLowStock($shop, $cartSnapshot);

        } catch (\RuntimeException $e) {
            Notification::make()
                ->title('Erreur de stock')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
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

    public function openCreditModal(): void
    {
        if (empty($this->cart)) {
            Notification::make()
                ->title('Panier vide')
                ->warning()
                ->send();

            return;
        }

        $this->showCreditModal = true;
    }

    public function completeCreditSale(): void
    {
        if (!$this->selectedCustomerId) {
            Notification::make()
                ->title('Sélectionnez un client')
                ->danger()
                ->send();
            return;
        }

        if (empty($this->cart)) {
            return;
        }

        $shop = Filament::getTenant();

        // Valider que le client appartient à ce commerce
        $customer = $shop->customers()->find($this->selectedCustomerId);
        if (!$customer) {
            Notification::make()
                ->title('Client invalide')
                ->body("Ce client n'appartient pas à ce commerce.")
                ->danger()
                ->send();
            return;
        }

        $total = $this->getTotal();

        try {
            $creditRef = DB::transaction(function () use ($shop, $customer, $total) {
                // Vérification du stock en temps réel avec verrou pessimiste
                foreach ($this->cart as $item) {
                    $product = $shop->products()
                        ->lockForUpdate()
                        ->find($item['product_id']);

                    if (!$product || $product->stock_qty < $item['quantity']) {
                        throw new \RuntimeException("Stock insuffisant pour « {$item['product_name']} ».");
                    }
                }

                $sale = Sale::create([
                    'shop_id'       => $shop->id,
                    'user_id'       => auth()->id(),
                    'customer_id'   => $customer->id,
                    'payment_type'  => 'credit',
                    'reference'     => Sale::generateReference($shop->id),
                    'status'        => 'completed',
                    'total_amount'  => $total,
                    'paid_amount'   => 0,
                    'change_amount' => 0,
                ]);

                foreach ($this->cart as $item) {
                    SaleItem::create([
                        'sale_id'      => $sale->id,
                        'product_id'   => $item['product_id'],
                        'product_name' => $item['product_name'],
                        'quantity'     => $item['quantity'],
                        'unit_price'   => $item['unit_price'],
                        'subtotal'     => $item['subtotal'],
                    ]);

                    StockMovement::create([
                        'shop_id'    => $shop->id,
                        'product_id' => $item['product_id'],
                        'user_id'    => auth()->id(),
                        'type'       => 'out',
                        'quantity'   => $item['quantity'],
                        'reason'     => "Vente crédit {$sale->reference}",
                    ]);
                }

                $credit = Credit::create([
                    'shop_id'          => $shop->id,
                    'sale_id'          => $sale->id,
                    'customer_id'      => $customer->id,
                    'user_id'          => auth()->id(),
                    'reference'        => Credit::generateReference($shop->id),
                    'status'           => 'pending',
                    'total_amount'     => $total,
                    'paid_amount'      => 0,
                    'remaining_amount' => $total,
                    'due_date'         => $this->creditDueDate,
                    'description'      => 'Vente à crédit depuis la caisse',
                    'note'             => $this->creditNote,
                ]);

                return $credit->reference;
            });

            Notification::make()
                ->title('✅ Vente à crédit enregistrée')
                ->body($customer->name . ' doit ' . number_format($total, 0, ',', ' ') . ' KMF')
                ->success()
                ->send();

            $cartSnapshot = $this->cart;
            $customerName = $customer->name;

            $this->clearCart();
            $this->showCreditModal    = false;
            $this->selectedCustomerId = null;
            $this->creditDueDate      = null;
            $this->creditNote         = '';

            $this->lastSale = [
                'reference' => $creditRef,
                'items'     => collect($cartSnapshot)->values()->toArray(),
                'total'     => (int) $total,
                'paid'      => 0,
                'change'    => 0,
                'note'      => '',
                'type'      => 'crédit',
                'customer'  => $customerName,
            ];
            $this->showReceiptModal = true;

            $this->notifyLowStock($shop, $cartSnapshot);

        } catch (\RuntimeException $e) {
            Notification::make()
                ->title('Erreur de stock')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function notifyLowStock(mixed $shop, array $cartSnapshot): void
    {
        try {
            $productIds = array_column($cartSnapshot, 'product_id');

            $products = $shop->products()
                ->with('unit')
                ->whereIn('id', $productIds)
                ->get()
                ->keyBy('id');

            $outOfStock = [];
            $lowStock   = [];

            foreach ($cartSnapshot as $item) {
                $product = $products->get($item['product_id']);
                if (!$product) continue;

                if ($product->stock_qty <= 0) {
                    $outOfStock[] = $product->name;
                } elseif ($product->isLowStock()) {
                    $unit = $product->unit?->abbreviation ?? '';
                    $lowStock[] = "{$product->name} (reste {$product->stock_qty} {$unit})";
                }
            }

            if (!empty($outOfStock)) {
                Notification::make()
                    ->title('🔴 Rupture de stock !')
                    ->body('À réapprovisionner : ' . implode(', ', $outOfStock))
                    ->danger()
                    ->persistent()
                    ->send();
            }

            if (!empty($lowStock)) {
                Notification::make()
                    ->title('🟠 Stock bas')
                    ->body(implode(' · ', $lowStock))
                    ->warning()
                    ->duration(8000)
                    ->send();
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
