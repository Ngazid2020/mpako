<x-filament-widgets::widget>
    <x-filament::section>

        {{-- En-tête --}}
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                🏆 Top 5 produits
            </h2>

            {{-- Filtre période --}}
            <select
                wire:model.live="period"
                class="text-sm rounded-lg border-gray-300 dark:border-gray-600
                       dark:bg-gray-700 dark:text-white py-1 px-2"
            >
                <option value="7days">7 jours</option>
                <option value="30days">30 jours</option>
                <option value="month">Ce mois</option>
            </select>
        </div>

        {{-- Liste des produits --}}
        @php $products = $this->getTopProducts(); @endphp

        @if($products->isEmpty())
            <div class="text-center py-8 text-gray-400 dark:text-gray-500">
                <x-heroicon-o-chart-bar class="w-10 h-10 mx-auto mb-2 opacity-30" />
                <p class="text-sm">Aucune vente sur cette période</p>
            </div>
        @else
            {{-- Trouver le max pour la barre de progression --}}
            @php $maxAmount = $products->max('total_amount'); @endphp

            <div class="space-y-3">
                @foreach($products as $index => $product)
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            {{-- Rang + Nom --}}
                            <div class="flex items-center gap-2 min-w-0">
                                <span @class([
                                    'flex-shrink-0 w-6 h-6 rounded-full text-xs font-bold flex items-center justify-center',
                                    'bg-yellow-400 text-yellow-900' => $index === 0,
                                    'bg-gray-300 text-gray-700 dark:bg-gray-600 dark:text-gray-200' => $index === 1,
                                    'bg-orange-300 text-orange-900' => $index === 2,
                                    'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' => $index > 2,
                                ])>
                                    {{ $index + 1 }}
                                </span>
                                <span class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                    {{ $product->product_name }}
                                </span>
                            </div>

                            {{-- Montant --}}
                            <span class="flex-shrink-0 text-sm font-bold text-primary-600 dark:text-primary-400 ml-2">
                                {{ number_format($product->total_amount, 0, ',', ' ') }} KMF
                            </span>
                        </div>

                        {{-- Barre de progression --}}
                        <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-1.5">
                            <div
                                class="h-1.5 rounded-full bg-primary-500 transition-all duration-500"
                                style="width: {{ $maxAmount > 0 ? ($product->total_amount / $maxAmount) * 100 : 0 }}%"
                            ></div>
                        </div>

                        {{-- Quantité vendue --}}
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                            {{ number_format($product->total_qty, 0) }} unité(s) vendue(s)
                        </p>
                    </div>
                @endforeach
            </div>
        @endif

    </x-filament::section>
</x-filament-widgets::widget>