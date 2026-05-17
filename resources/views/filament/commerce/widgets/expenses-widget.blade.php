<x-filament-widgets::widget>
    <x-filament::section>

        {{-- En-tête --}}
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                💳 Dépenses
            </h2>

            <select
                wire:model.live="period"
                class="text-sm rounded-lg border-gray-300 dark:border-gray-600
                       dark:bg-gray-700 dark:text-white py-1 px-2">
                <option value="today">Aujourd'hui</option>
                <option value="week">Cette semaine</option>
                <option value="month">Ce mois</option>
            </select>
        </div>

        {{-- Total --}}
        <div class="text-center p-4 bg-red-50 dark:bg-red-900/20 rounded-xl mb-4">
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Total dépensé</p>
            <p class="text-3xl font-bold text-red-600 dark:text-red-400">
                {{ number_format($this->getTotalExpenses(), 0, ',', ' ') }}
                <span class="text-base">KMF</span>
            </p>
        </div>

        {{-- Par catégorie --}}
        @php $categories = $this->getExpensesByCategory(); @endphp

        @if($categories->isEmpty())
        <div class="text-center py-6 text-gray-400 dark:text-gray-500">
            <x-heroicon-o-check-circle class="w-10 h-10 mx-auto mb-2 text-green-400" />
            <p class="text-sm">Aucune dépense sur cette période</p>
        </div>
        @else
        @php $maxTotal = $categories->max('total'); @endphp

        <div class="space-y-3">
            @foreach($categories as $cat)
            <div>
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ $cat['name'] }}
                        <span class="text-xs text-gray-400 ml-1">
                            ({{ $cat['count'] }})
                        </span>
                    </span>
                    <span class="text-sm font-bold text-red-600 dark:text-red-400">
                        {{ number_format($cat['total'], 0, ',', ' ') }} KMF
                    </span>
                </div>

                {{-- Barre de progression --}}
                <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-1.5">
                    <div
                        class="h-1.5 rounded-full transition-all duration-500"
                        style="width: {{ $maxTotal > 0 ? ($cat['total'] / $maxTotal) * 100 : 0 }}%;
                                    background-color: {{ $cat['color'] }};
                                "></div>
                </div>
            </div>
            @endforeach
        </div>
        @endif

    </x-filament::section>
</x-filament-widgets::widget>