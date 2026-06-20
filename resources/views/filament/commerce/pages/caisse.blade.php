<x-filament-panels::page>

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- GRILLE PRINCIPALE                                      --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ───────────────────────────────────────────────── --}}
        {{-- COLONNE GAUCHE : Recherche + Résultats + Panier   --}}
        {{-- ───────────────────────────────────────────────── --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Recherche & Scanner code-barres --}}
            <div
                x-data="caisseScanner"
                @keydown.escape.window="stopScan()"
                class="bg-white dark:bg-gray-800 rounded-xl shadow p-4"
            >
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    🔍 Rechercher un produit
                </label>
                <div class="flex gap-2">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Nom ou code-barres..."
                        class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-lg px-4 py-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        autofocus
                    />
                    <button
                        @click="scanning ? stopScan() : startScan()"
                        type="button"
                        :disabled="!supported"
                        class="px-4 rounded-lg transition-colors"
                        :class="scanning
                            ? 'bg-red-100 dark:bg-red-900/30 text-red-600'
                            : supported
                                ? 'bg-gray-100 dark:bg-gray-700 hover:bg-primary-100 dark:hover:bg-primary-900/30 text-gray-600 dark:text-gray-300'
                                : 'bg-gray-50 dark:bg-gray-800 text-gray-300 dark:text-gray-600 cursor-not-allowed'"
                        :title="supported ? 'Scanner un code-barres' : 'Non disponible sur ce navigateur'"
                    >
                        <span x-show="!scanning" class="text-2xl">📷</span>
                        <span x-show="scanning" class="text-2xl">⏹</span>
                    </button>
                </div>

                {{-- Vue caméra --}}
                <div x-show="scanning" x-cloak class="mt-3 relative rounded-xl overflow-hidden bg-black">
                    <video
                        x-ref="camvideo"
                        autoplay muted playsinline
                        class="w-full max-h-48 object-cover"
                    ></video>
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                        <div class="w-52 h-28 border-2 border-primary-400/80 rounded-lg"></div>
                    </div>
                    <p class="absolute bottom-2 inset-x-0 text-center text-xs text-white/70">
                        Pointez vers le code-barres · Échap pour annuler
                    </p>
                </div>
            </div>

            {{-- Résultats de recherche --}}
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
                                class="w-full flex items-center justify-between p-4 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors text-left group"
                            >
                                <div class="flex-1">
                                    <p class="font-semibold text-gray-900 dark:text-white group-hover:text-primary-600">
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
                                    @php
                                        $stockClass = 'bg-green-100 text-green-700';
                                        if ($product->stock_qty <= 0) {
                                            $stockClass = 'bg-red-100 text-red-700';
                                        } elseif ($product->stock_qty <= $product->stock_alert) {
                                            $stockClass = 'bg-orange-100 text-orange-700';
                                        }
                                    @endphp
                                    <span class="text-xs px-2 py-1 rounded-full font-medium {{ $stockClass }}">
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

                <div class="flex items-center justify-between p-4 border-b dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                    <h2 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        🛒 Panier
                        @if($this->getCartCount() > 0)
                            <span class="bg-primary-500 text-white text-xs rounded-full px-2 py-0.5">
                                {{ $this->getCartCount() }}
                            </span>
                        @endif
                    </h2>

                    @if(!empty($cart))
                        <button
                            wire:click="clearCart"
                            wire:confirm="Vider le panier ?"
                            class="text-sm text-red-500 hover:text-red-700 transition-colors"
                        >
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
                                        {{ number_format($item['unit_price'], 0, ',', ' ') }} KMF / {{ $item['unit'] }}
                                    </p>
                                </div>

                                <div class="flex items-center gap-2">
                                    <button
                                        wire:click="updateQuantity({{ $productId }}, {{ $item['quantity'] - 1 }})"
                                        class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 hover:bg-red-100 dark:hover:bg-red-900/30 flex items-center justify-center text-gray-700 dark:text-gray-300 transition-colors font-bold"
                                    >−</button>

                                    <input
                                        type="number"
                                        value="{{ $item['quantity'] }}"
                                        wire:change="updateQuantity({{ $productId }}, $event.target.value)"
                                        class="w-16 text-center rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white font-semibold"
                                        min="0"
                                        max="{{ $item['stock_max'] }}"
                                        step="1"
                                    />

                                    <button
                                        wire:click="updateQuantity({{ $productId }}, {{ $item['quantity'] + 1 }})"
                                        class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 hover:bg-green-100 dark:hover:bg-green-900/30 flex items-center justify-center text-gray-700 dark:text-gray-300 transition-colors font-bold"
                                    >+</button>
                                </div>

                                <div class="text-right w-28">
                                    <p class="font-bold text-gray-900 dark:text-white">
                                        {{ number_format($item['subtotal'], 0, ',', ' ') }}
                                        <span class="text-xs text-gray-500">KMF</span>
                                    </p>
                                </div>

                                <button
                                    wire:click="removeFromCart({{ $productId }})"
                                    class="text-red-400 hover:text-red-600 transition-colors"
                                >
                                    <x-heroicon-o-x-mark class="w-5 h-5" />
                                </button>
                            </div>
                        @endforeach
                    </div>

                    <div class="p-4 bg-gray-50 dark:bg-gray-700 border-t dark:border-gray-600">
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-semibold text-gray-700 dark:text-gray-300">Total</span>
                            <span class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                                {{ number_format($this->getTotal(), 0, ',', ' ') }} KMF
                            </span>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- ───────────────────────────────────────── --}}
        {{-- COLONNE DROITE : Encaissement + Stats     --}}
        {{-- ───────────────────────────────────────── --}}
        <div class="space-y-4">

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 space-y-5 sticky top-4">

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
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-2xl text-center font-bold py-3 focus:ring-2 focus:ring-primary-500"
                        min="0"
                    />

                    <div class="grid grid-cols-3 gap-2 mt-2">
                        @foreach([500, 1000, 2000, 5000, 10000, 20000] as $amount)
                            <button
                                wire:click="$set('paidAmount', {{ $amount }})"
                                class="py-2 px-1 rounded-lg text-sm font-medium bg-gray-100 dark:bg-gray-700 hover:bg-primary-100 dark:hover:bg-primary-900/30 text-gray-700 dark:text-gray-300 transition-colors"
                            >
                                {{ number_format($amount, 0, ',', ' ') }}
                            </button>
                        @endforeach
                    </div>

                    @if($this->getTotal() > 0)
                        <button
                            wire:click="$set('paidAmount', {{ $this->getTotal() }})"
                            class="w-full mt-2 py-2 rounded-lg text-sm font-medium bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 transition-colors"
                        >
                            Montant exact
                        </button>
                    @endif
                </div>

                {{-- Monnaie à rendre --}}
                @if($paidAmount > 0)
                    @php
                        $change = $this->getChange();
                        $isPositive = $change >= 0;
                    @endphp
                    <div class="text-center p-4 rounded-xl {{ $isPositive ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20' }}">
                        <p class="text-sm font-medium mb-1 {{ $isPositive ? 'text-green-600' : 'text-red-500' }}">
                            {{ $isPositive ? '💚 Monnaie à rendre' : '⚠️ Manque' }}
                        </p>
                        <p class="text-3xl font-bold {{ $isPositive ? 'text-green-600 dark:text-green-400' : 'text-red-500 dark:text-red-400' }}">
                            {{ number_format(abs($change), 0, ',', ' ') }}
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
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-2"
                    />
                </div>

                {{-- Valider vente comptant --}}
                @php $canComplete = !empty($cart) && $paidAmount >= $this->getTotal(); @endphp
                <button
                    wire:click="completeSale"
                    wire:loading.attr="disabled"
                    class="w-full py-4 rounded-xl font-bold text-lg transition-colors {{ $canComplete ? 'bg-primary-600 hover:bg-primary-700 text-white shadow-lg' : 'bg-gray-200 dark:bg-gray-700 text-gray-400 cursor-not-allowed' }}"
                    {{ $canComplete ? '' : 'disabled' }}
                >
                    <span wire:loading.remove wire:target="completeSale">✅ Valider la vente</span>
                    <span wire:loading wire:target="completeSale">⏳ Enregistrement...</span>
                </button>

                {{-- Vente à crédit --}}
                <button
                    wire:click="openCreditModal"
                    class="w-full py-3 rounded-xl font-bold text-lg bg-orange-500 hover:bg-orange-600 text-white transition-colors"
                >
                    📒 Vente à crédit
                </button>
            </div>

            {{-- Ventes du jour --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
                <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">📊 Aujourd'hui</h3>

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
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $todayCount }}</p>
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

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- MODAL REÇU                                            --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    @if(!empty($lastSale))
        @php
            $receipt = $lastSale;
            $waLines  = "*🧾 REÇU — {$receipt['reference']}*\n";
            $waLines .= str_repeat('—', 22) . "\n";
            foreach ($receipt['items'] as $it) {
                $waLines .= "• {$it['product_name']} × {$it['quantity']} {$it['unit']}\n";
                $waLines .= "  " . number_format($it['subtotal'], 0, ',', ' ') . " KMF\n";
            }
            $waLines .= str_repeat('—', 22) . "\n";
            $waLines .= "*TOTAL : " . number_format($receipt['total'], 0, ',', ' ') . " KMF*\n";
            if ($receipt['type'] === 'comptant') {
                $waLines .= "Payé : " . number_format($receipt['paid'], 0, ',', ' ') . " KMF\n";
                if ($receipt['change'] > 0) {
                    $waLines .= "Monnaie : " . number_format($receipt['change'], 0, ',', ' ') . " KMF";
                }
            } else {
                $waLines .= "📒 Crédit · Client : {$receipt['customer']}";
            }
        @endphp

        <div
            x-data
            x-show="$wire.showReceiptModal"
            x-cloak
            x-transition:enter="transition-opacity ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            @keydown.escape.window="$wire.closeReceipt()"
        >
            <div
                x-show="$wire.showReceiptModal"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto"
            >
                <div class="flex items-center justify-between p-6 border-b dark:border-gray-700">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">🧾 Reçu</h2>
                        <p class="text-xs text-gray-400 font-mono mt-0.5">{{ $receipt['reference'] }}</p>
                    </div>
                    <button wire:click="closeReceipt" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                        <x-heroicon-o-x-mark class="w-6 h-6" />
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    @if($receipt['type'] === 'crédit')
                        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg px-4 py-2 flex items-center gap-2">
                            <span class="text-orange-500">📒</span>
                            <span class="text-sm font-medium text-orange-700 dark:text-orange-300">
                                Vente à crédit · {{ $receipt['customer'] }}
                            </span>
                        </div>
                    @endif

                    <div class="space-y-2">
                        @foreach($receipt['items'] as $it)
                            <div class="flex justify-between items-start text-sm">
                                <span class="text-gray-700 dark:text-gray-300">
                                    {{ $it['product_name'] }}
                                    <span class="text-gray-400">× {{ $it['quantity'] }} {{ $it['unit'] }}</span>
                                </span>
                                <span class="font-medium text-gray-900 dark:text-white whitespace-nowrap ml-4">
                                    {{ number_format($it['subtotal'], 0, ',', ' ') }} KMF
                                </span>
                            </div>
                        @endforeach
                    </div>

                    <div class="border-t dark:border-gray-700 pt-3 space-y-1">
                        <div class="flex justify-between text-lg font-bold">
                            <span class="text-gray-900 dark:text-white">Total</span>
                            <span class="text-primary-600 dark:text-primary-400">
                                {{ number_format($receipt['total'], 0, ',', ' ') }} KMF
                            </span>
                        </div>
                        @if($receipt['type'] === 'comptant')
                            <div class="flex justify-between text-sm text-gray-500 dark:text-gray-400">
                                <span>Reçu</span>
                                <span>{{ number_format($receipt['paid'], 0, ',', ' ') }} KMF</span>
                            </div>
                            @if($receipt['change'] > 0)
                                <div class="flex justify-between text-sm font-semibold text-green-600 dark:text-green-400">
                                    <span>Monnaie à rendre</span>
                                    <span>{{ number_format($receipt['change'], 0, ',', ' ') }} KMF</span>
                                </div>
                            @endif
                        @endif
                    </div>

                    @if($receipt['note'])
                        <p class="text-xs text-gray-400 italic">Note : {{ $receipt['note'] }}</p>
                    @endif

                    <div class="flex gap-3 pt-2">
                        <a
                            href="https://wa.me/?text={{ rawurlencode($waLines) }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex-1 py-3 rounded-xl bg-green-500 hover:bg-green-600 text-white font-bold text-center text-sm transition-colors"
                        >
                            📱 Partager WhatsApp
                        </a>
                        <button
                            onclick="(function(){var w=window.open('','_blank','width=400,height=600');w.document.write(document.getElementById('receipt-print-content').innerHTML);w.document.close();w.focus();w.print();w.close();})()"
                            type="button"
                            class="px-4 py-3 rounded-xl bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 transition-colors"
                            title="Imprimer"
                        >🖨️</button>
                        <button
                            wire:click="closeReceipt"
                            class="px-4 py-3 rounded-xl bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium transition-colors"
                        >Fermer</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Contenu caché pour impression --}}
        <div id="receipt-print-content" style="display:none">
            <style>body{font-family:monospace;padding:16px;max-width:320px;margin:auto}h2{text-align:center}table{width:100%;border-collapse:collapse}td{padding:2px 0}hr{border:none;border-top:1px dashed #000}</style>
            <h2>🧾 {{ $receipt['reference'] }}</h2>
            <hr>
            <table>
                @foreach($receipt['items'] as $it)
                    <tr>
                        <td>{{ $it['product_name'] }} ×{{ $it['quantity'] }}</td>
                        <td style="text-align:right">{{ number_format($it['subtotal'], 0, ',', ' ') }}</td>
                    </tr>
                @endforeach
            </table>
            <hr>
            <table>
                <tr>
                    <td><strong>TOTAL</strong></td>
                    <td style="text-align:right"><strong>{{ number_format($receipt['total'], 0, ',', ' ') }} KMF</strong></td>
                </tr>
                @if($receipt['type'] === 'comptant')
                    <tr>
                        <td>Payé</td>
                        <td style="text-align:right">{{ number_format($receipt['paid'], 0, ',', ' ') }} KMF</td>
                    </tr>
                    @if($receipt['change'] > 0)
                        <tr>
                            <td>Monnaie</td>
                            <td style="text-align:right">{{ number_format($receipt['change'], 0, ',', ' ') }} KMF</td>
                        </tr>
                    @endif
                @else
                    <tr><td colspan="2">📒 Crédit — {{ $receipt['customer'] }}</td></tr>
                @endif
            </table>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- MODAL VENTE À CRÉDIT                                  --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <div
        x-data
        x-show="$wire.showCreditModal"
        x-cloak
        x-transition:enter="transition-opacity ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    >
        <div
            x-show="$wire.showCreditModal"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 translate-y-2"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-2"
            class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg p-6"
        >
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">📒 Vente à crédit</h2>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Client</label>
                <select
                    wire:model="selectedCustomerId"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                >
                    <option value="">Sélectionner un client</option>
                    @foreach(
                        \Filament\Facades\Filament::getTenant()
                            ->customers()
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->get()
                        as $customer
                    )
                        <option value="{{ $customer->id }}">
                            {{ $customer->name }}
                            @if($customer->balance > 0)
                                — Dette: {{ number_format($customer->balance, 0, ',', ' ') }} KMF
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Échéance (optionnel)</label>
                <input
                    type="date"
                    wire:model="creditDueDate"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                />
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Note</label>
                <textarea
                    wire:model="creditNote"
                    rows="2"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    placeholder="Optionnel..."
                ></textarea>
            </div>

            <div class="bg-orange-50 dark:bg-orange-900/20 rounded-xl p-4 mb-6">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Montant à crédit</span>
                    <span class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                        {{ number_format($this->getTotal(), 0, ',', ' ') }} KMF
                    </span>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <button
                    wire:click="$set('showCreditModal', false)"
                    class="px-4 py-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                >
                    Annuler
                </button>
                <button
                    wire:click="completeCreditSale"
                    wire:loading.attr="disabled"
                    class="px-5 py-2 rounded-lg bg-orange-500 hover:bg-orange-600 text-white font-medium transition-colors"
                >
                    <span wire:loading.remove wire:target="completeCreditSale">✅ Confirmer le crédit</span>
                    <span wire:loading wire:target="completeCreditSale">⏳ Enregistrement...</span>
                </button>
            </div>
        </div>
    </div>

</x-filament-panels::page>
