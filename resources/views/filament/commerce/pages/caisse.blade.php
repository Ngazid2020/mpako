<x-filament-panels::page>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ════════════════════════════════════════ --}}
        {{-- COLONNE GAUCHE : Recherche + Résultats  --}}
        {{-- ════════════════════════════════════════ --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Champ de recherche --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    🔍 Rechercher un produit
                </label>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Nom du produit ou code-barres..."
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600
                           dark:bg-gray-700 dark:text-white text-lg px-4 py-3
                           focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    autofocus />
            </div>

            {{-- Résultats de la recherche --}}
            @if($searchResults->isNotEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
                <div class="p-3 bg-gray-50 dark:bg-gray-700 border-b dark:border-gray-600">
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $searchResults->count() }} résultat(s)
                    </span>
                </div>
                <div class="divide-y dark:divide-gray-700">
                    @foreach($searchResults as $product)
                    <button
                        wire:click="addToCart({{ $product->id }})"
                        class="w-full flex items-center justify-between p-4
                                       hover:bg-primary-50 dark:hover:bg-primary-900/20
                                       transition-colors text-left group">
                        <div class="flex-1">
                            <p class="font-semibold text-gray-900 dark:text-white
                                              group-hover:text-primary-600">
                                {{ $product->name }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $product->category?->name }}
                                @if($product->unit)
                                · {{ $product->unit->abbreviation }}
                                @endif
                            </p>
                        </div>

                        <div class="text-center mx-4">
                            <span @class([ 'text-xs px-2 py-1 rounded-full font-medium' , 'bg-green-100 text-green-700'=> $product->stock_qty > $product->stock_alert,
                                'bg-orange-100 text-orange-700' => $product->stock_qty > 0 && $product->stock_qty <= $product->stock_alert,
                                    'bg-red-100 text-red-700' => $product->stock_qty <= 0,
                                        ])>
                                        Stock: {{ $product->stock_qty }}
                            </span>
                        </div>

                        <div class="text-right">
                            <p class="font-bold text-primary-600 dark:text-primary-400 text-lg">
                                {{ number_format($product->sell_price, 0, ',', ' ') }}
                                <span class="text-xs">KMF</span>
                            </p>
                        </div>
                    </button>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Panier --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">

                <div class="flex items-center justify-between p-4 border-b dark:border-gray-700
                            bg-gray-50 dark:bg-gray-700">
                    <h2 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        🛒 Panier
                        @if($this->getCartCount() > 0)
                        <span class="bg-primary-500 text-white text-xs rounded-full
                                         px-2 py-0.5">
                            {{ $this->getCartCount() }}
                        </span>
                        @endif
                    </h2>

                    @if(!empty($cart))
                    <button
                        wire:click="clearCart"
                        wire:confirm="Vider le panier ?"
                        class="text-sm text-red-500 hover:text-red-700 transition-colors">
                        Vider
                    </button>
                    @endif
                </div>

                @if(empty($cart))
                <div class="p-12 text-center text-gray-400 dark:text-gray-500">
                    <x-heroicon-o-shopping-cart class="w-12 h-12 mx-auto mb-3 opacity-30" />
                    <p>Le panier est vide</p>
                    <p class="text-sm mt-1">Recherchez un produit ci-dessus</p>
                </div>
                @else
                <div class="divide-y dark:divide-gray-700">
                    @foreach($cart as $productId => $item)
                    <div class="flex items-center gap-4 p-4">

                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-900 dark:text-white truncate">
                                {{ $item['product_name'] }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ number_format($item['unit_price'], 0, ',', ' ') }} KMF
                                / {{ $item['unit'] }}
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                wire:click="updateQuantity({{ $productId }}, {{ $item['quantity'] - 1 }})"
                                class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700
                                               hover:bg-red-100 dark:hover:bg-red-900/30
                                               flex items-center justify-center
                                               text-gray-700 dark:text-gray-300
                                               transition-colors font-bold">
                                −
                            </button>

                            <input
                                type="number"
                                value="{{ $item['quantity'] }}"
                                wire:change="updateQuantity({{ $productId }}, $event.target.value)"
                                class="w-16 text-center rounded-lg border-gray-300
                                               dark:border-gray-600 dark:bg-gray-700
                                               dark:text-white font-semibold"
                                min="0"
                                max="{{ $item['stock_max'] }}"
                                step="1" />

                            <button
                                wire:click="updateQuantity({{ $productId }}, {{ $item['quantity'] + 1 }})"
                                class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700
                                               hover:bg-green-100 dark:hover:bg-green-900/30
                                               flex items-center justify-center
                                               text-gray-700 dark:text-gray-300
                                               transition-colors font-bold">
                                +
                            </button>
                        </div>

                        <div class="text-right w-28">
                            <p class="font-bold text-gray-900 dark:text-white">
                                {{ number_format($item['subtotal'], 0, ',', ' ') }}
                                <span class="text-xs text-gray-500">KMF</span>
                            </p>
                        </div>

                        <button
                            wire:click="removeFromCart({{ $productId }})"
                            class="text-red-400 hover:text-red-600 transition-colors">
                            <x-heroicon-o-x-mark class="w-5 h-5" />
                        </button>

                    </div>
                    @endforeach
                </div>

                <div class="p-4 bg-gray-50 dark:bg-gray-700 border-t dark:border-gray-600">
                    <div class="flex justify-between items-center">
                        <span class="text-lg font-semibold text-gray-700 dark:text-gray-300">
                            Total
                        </span>
                        <span class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                            {{ number_format($this->getTotal(), 0, ',', ' ') }} KMF
                        </span>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- ═══════════════════════════════════ --}}
        {{-- COLONNE DROITE : Encaissement      --}}
        {{-- ═══════════════════════════════════ --}}
        <div class="space-y-4">

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 space-y-5
                        sticky top-4">

                {{-- Total à payer --}}
                <div class="text-center p-4 bg-primary-50 dark:bg-primary-900/20 rounded-xl">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Total à payer</p>
                    <p class="text-4xl font-bold text-primary-600 dark:text-primary-400">
                        {{ number_format($this->getTotal(), 0, ',', ' ') }}
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">KMF</p>
                </div>

                {{-- Montant reçu --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        💵 Montant reçu (KMF)
                    </label>
                    <input
                        type="number"
                        wire:model.live="paidAmount"
                        placeholder="0"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600
                               dark:bg-gray-700 dark:text-white text-2xl text-center
                               font-bold py-3 focus:ring-2 focus:ring-primary-500"
                        min="0" />

                    <div class="grid grid-cols-3 gap-2 mt-2">
                        @foreach([500, 1000, 2000, 5000, 10000, 20000] as $amount)
                        <button
                            wire:click="$set('paidAmount', {{ $amount }})"
                            class="py-2 px-1 rounded-lg text-sm font-medium
                                       bg-gray-100 dark:bg-gray-700
                                       hover:bg-primary-100 dark:hover:bg-primary-900/30
                                       text-gray-700 dark:text-gray-300
                                       transition-colors">
                            {{ number_format($amount, 0, ',', ' ') }}
                        </button>
                        @endforeach
                    </div>

                    @if($this->getTotal() > 0)
                    <button
                        wire:click="$set('paidAmount', {{ $this->getTotal() }})"
                        class="w-full mt-2 py-2 rounded-lg text-sm font-medium
                                   bg-gray-200 dark:bg-gray-600
                                   hover:bg-gray-300 dark:hover:bg-gray-500
                                   text-gray-700 dark:text-gray-200
                                   transition-colors">
                        Montant exact
                    </button>
                    @endif
                </div>

                {{-- Monnaie à rendre --}}
                @if($paidAmount > 0)
                <div @class([ 'text-center p-4 rounded-xl' , 'bg-green-50 dark:bg-green-900/20'=> $this->getChange() >= 0,
                    'bg-red-50 dark:bg-red-900/20' => $this->getChange() < 0,
                        ])>
                        <p class="text-sm font-medium mb-1
                                  {{ $this->getChange() >= 0 ? 'text-green-600' : 'text-red-500' }}">
                            {{ $this->getChange() >= 0 ? '💚 Monnaie à rendre' : '⚠️ Manque' }}
                        </p>
                        <p class="text-3xl font-bold
                                  {{ $this->getChange() >= 0
                                      ? 'text-green-600 dark:text-green-400'
                                      : 'text-red-500 dark:text-red-400' }}">
                            {{ number_format(abs($this->getChange()), 0, ',', ' ') }}
                            <span class="text-base">KMF</span>
                        </p>
                </div>
                @endif

                {{-- Note --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        📝 Note (optionnel)
                    </label>
                    <input
                        type="text"
                        wire:model="note"
                        placeholder="Ex: client habituel..."
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600
                               dark:bg-gray-700 dark:text-white text-sm py-2" />
                </div>

                {{-- Bouton valider --}}
                <button
                    wire:click="completeSale"
                    wire:loading.attr="disabled"
                    @class([ 'w-full py-4 rounded-xl font-bold text-lg transition-all' , 'bg-primary-600 hover:bg-primary-700 text-white shadow-lg hover:shadow-xl'=> !empty($cart) && $paidAmount >= $this->getTotal(),
                    'bg-gray-200 dark:bg-gray-700 text-gray-400 cursor-not-allowed'
                    => empty($cart) || $paidAmount < $this->getTotal(),
                        ])
                        {{ empty($cart) || $paidAmount < $this->getTotal() ? 'disabled' : '' }}
                        >
                        <span wire:loading.remove wire:target="completeSale">
                            ✅ Valider la vente
                        </span>
                        <span wire:loading wire:target="completeSale">
                            ⏳ Enregistrement...
                        </span>
                </button>

            </div>

            {{-- Ventes du jour --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
                <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                    📊 Aujourd'hui
                </h3>
                @php
                $shop = \Filament\Facades\Filament::getTenant();
                $todaySales = $shop->sales()
                ->whereDate('created_at', today())
                ->where('status', 'completed')
                ->get();
                $todayTotal = $todaySales->sum('total_amount');
                $todayCount = $todaySales->count();
                @endphp

                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                            {{ $todayCount }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Ventes</p>
                    </div>
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3 text-center">
                        <p class="text-lg font-bold text-green-600 dark:text-green-400">
                            {{ number_format($todayTotal, 0, ',', ' ') }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">KMF encaissés</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

</x-filament-panels::page>