<x-filament-widgets::widget>
    <x-filament::section>

        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                ⚠️ Produits à réapprovisionner
            </h2>

            @php $products = $this->getLowStockProducts(); @endphp

            @if($products->isNotEmpty())
                <span class="bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400
                             text-xs font-bold px-2 py-1 rounded-full">
                    {{ $products->count() }} alerte(s)
                </span>
            @endif
        </div>

        @if($products->isEmpty())
            <div class="text-center py-6 text-gray-400 dark:text-gray-500">
                <x-heroicon-o-check-circle class="w-10 h-10 mx-auto mb-2 text-green-400" />
                <p class="text-sm font-medium text-green-600 dark:text-green-400">
                    Tous les stocks sont suffisants 👍
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left border-b dark:border-gray-700">
                            <th class="pb-2 font-semibold text-gray-600 dark:text-gray-400">Produit</th>
                            <th class="pb-2 font-semibold text-gray-600 dark:text-gray-400">Catégorie</th>
                            <th class="pb-2 font-semibold text-gray-600 dark:text-gray-400 text-center">Stock actuel</th>
                            <th class="pb-2 font-semibold text-gray-600 dark:text-gray-400 text-center">Seuil alerte</th>
                            <th class="pb-2 font-semibold text-gray-600 dark:text-gray-400 text-center">Statut</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-gray-700">
                        @foreach($products as $product)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">

                                {{-- Produit --}}
                                <td class="py-3">
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        {{ $product->name }}
                                    </p>
                                </td>

                                {{-- Catégorie --}}
                                <td class="py-3">
                                    @if($product->category)
                                        <span class="text-xs px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-700
                                                     text-gray-600 dark:text-gray-300">
                                            {{ $product->category->name }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>

                                {{-- Stock actuel --}}
                                <td class="py-3 text-center">
                                    <span @class([
                                        'font-bold text-lg',
                                        'text-red-600 dark:text-red-400'    => $product->stock_qty <= 0,
                                        'text-orange-500 dark:text-orange-400' => $product->stock_qty > 0,
                                    ])>
                                        {{ $product->stock_qty }}
                                    </span>
                                    <span class="text-xs text-gray-400 ml-1">
                                        {{ $product->unit?->abbreviation }}
                                    </span>
                                </td>

                                {{-- Seuil --}}
                                <td class="py-3 text-center text-gray-500 dark:text-gray-400">
                                    {{ $product->stock_alert }}
                                    <span class="text-xs ml-1">
                                        {{ $product->unit?->abbreviation }}
                                    </span>
                                </td>

                                {{-- Statut --}}
                                <td class="py-3 text-center">
                                    @if($product->stock_qty <= 0)
                                        <span class="inline-flex items-center gap-1 bg-red-100 text-red-700
                                                     dark:bg-red-900/30 dark:text-red-400
                                                     text-xs font-medium px-2 py-1 rounded-full">
                                            🔴 Rupture
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 bg-orange-100 text-orange-700
                                                     dark:bg-orange-900/30 dark:text-orange-400
                                                     text-xs font-medium px-2 py-1 rounded-full">
                                            🟠 Stock bas
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

    </x-filament::section>
</x-filament-widgets::widget>