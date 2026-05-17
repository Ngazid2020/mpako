<x-filament-panels::page>

    {{-- ══════════════════════════════════════════ --}}
    {{-- FILTRES DE PÉRIODE                         --}}
    {{-- ══════════════════════════════════════════ --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4
                flex flex-wrap items-center gap-4">

        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
            Période :
        </span>

        {{-- Sélecteur de période rapide --}}
        <div class="flex gap-2 flex-wrap">
            @foreach([
                'today' => "Aujourd'hui",
                'week'  => 'Cette semaine',
                'month' => 'Par mois',
                'year'  => 'Par année',
            ] as $value => $label)
                <button
                    wire:click="$set('period', '{{ $value }}')"
                    @class([
                        'px-3 py-1.5 rounded-lg text-sm font-medium transition-colors',
                        'bg-primary-600 text-white' => $period === $value,
                        'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300
                         hover:bg-primary-50 dark:hover:bg-primary-900/20' => $period !== $value,
                    ])
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Sélecteur mois --}}
        @if($period === 'month')
            <input
                type="month"
                wire:model.live="month"
                class="rounded-lg border-gray-300 dark:border-gray-600
                       dark:bg-gray-700 dark:text-white text-sm py-1.5 px-3"
                max="{{ now()->format('Y-m') }}"
            />
        @endif

        {{-- Sélecteur année --}}
        @if($period === 'year')
            <select
                wire:model.live="year"
                class="rounded-lg border-gray-300 dark:border-gray-600
                       dark:bg-gray-700 dark:text-white text-sm py-1.5 px-3"
            >
                @for($y = now()->year; $y >= now()->year - 3; $y--)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endfor
            </select>
        @endif

        {{-- Label période active --}}
        <span class="ml-auto text-sm font-medium text-primary-600 dark:text-primary-400">
            {{ $this->getPeriodDates()['label'] }}
        </span>
    </div>

    {{-- ══════════════════════════════════════════ --}}
    {{-- KPI PRINCIPAUX                             --}}
    {{-- ══════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">

        {{-- Ventes --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    💰 Ventes
                </span>
                <span class="text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-600
                             dark:text-blue-400 px-2 py-0.5 rounded-full">
                    {{ $this->getSalesCount() }} vente(s)
                </span>
            </div>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                {{ number_format($this->getTotalSales(), 0, ',', ' ') }}
                <span class="text-sm font-normal text-gray-400">KMF</span>
            </p>
        </div>

        {{-- Achats --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    🛍️ Achats
                </span>
                <span class="text-xs bg-orange-100 dark:bg-orange-900/30 text-orange-600
                             dark:text-orange-400 px-2 py-0.5 rounded-full">
                    Marchandises
                </span>
            </div>
            <p class="text-2xl font-bold text-orange-500 dark:text-orange-400">
                {{ number_format($this->getTotalPurchases(), 0, ',', ' ') }}
                <span class="text-sm font-normal text-gray-400">KMF</span>
            </p>
        </div>

        {{-- Dépenses --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    💳 Dépenses
                </span>
                <span class="text-xs bg-red-100 dark:bg-red-900/30 text-red-600
                             dark:text-red-400 px-2 py-0.5 rounded-full">
                    Charges
                </span>
            </div>
            <p class="text-2xl font-bold text-red-500 dark:text-red-400">
                {{ number_format($this->getTotalExpenses(), 0, ',', ' ') }}
                <span class="text-sm font-normal text-gray-400">KMF</span>
            </p>
        </div>

        {{-- Bénéfice net --}}
        <div @class([
            'rounded-xl shadow p-5',
            'bg-green-50 dark:bg-green-900/20' => $this->getNetProfit() >= 0,
            'bg-red-50 dark:bg-red-900/20'     => $this->getNetProfit() < 0,
        ])>
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    📈 Bénéfice net
                </span>
                <span @class([
                    'text-xs px-2 py-0.5 rounded-full font-medium',
                    'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                        => $this->getNetProfit() >= 0,
                    'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                        => $this->getNetProfit() < 0,
                ])>
                    {{ $this->getMarginPercent() }} %
                </span>
            </div>
            <p @class([
                'text-2xl font-bold',
                'text-green-600 dark:text-green-400' => $this->getNetProfit() >= 0,
                'text-red-600 dark:text-red-400'     => $this->getNetProfit() < 0,
            ])>
                {{ $this->getNetProfit() >= 0 ? '+' : '' }}
                {{ number_format($this->getNetProfit(), 0, ',', ' ') }}
                <span class="text-sm font-normal text-gray-400">KMF</span>
            </p>
        </div>
    </div>

    {{-- ══════════════════════════════════════════ --}}
    {{-- BARRE DE DÉCOMPOSITION VISUELLE            --}}
    {{-- ══════════════════════════════════════════ --}}
    @php
        $sales     = $this->getTotalSales();
        $purchases = $this->getTotalPurchases();
        $expenses  = $this->getTotalExpenses();
        $profit    = $this->getNetProfit();
        $total     = max($sales, 1);

        $purchasePct = min(100, round(($purchases / $total) * 100));
        $expensePct  = min(100, round(($expenses  / $total) * 100));
        $profitPct   = max(0,   round(($profit    / $total) * 100));
    @endphp

    @if($sales > 0)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5">
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-3">
                Décomposition du chiffre d'affaires
            </p>

            {{-- Barre de progression décomposée --}}
            <div class="flex rounded-full overflow-hidden h-6 mb-3">
                <div
                    class="bg-orange-400 flex items-center justify-center
                           text-white text-xs font-medium transition-all"
                    style="width: {{ $purchasePct }}%"
                    title="Achats : {{ $purchasePct }}%"
                >
                    @if($purchasePct > 5) {{ $purchasePct }}% @endif
                </div>
                <div
                    class="bg-red-400 flex items-center justify-center
                           text-white text-xs font-medium transition-all"
                    style="width: {{ $expensePct }}%"
                    title="Dépenses : {{ $expensePct }}%"
                >
                    @if($expensePct > 5) {{ $expensePct }}% @endif
                </div>
                <div
                    class="bg-green-400 flex items-center justify-center
                           text-white text-xs font-medium transition-all flex-1"
                    title="Bénéfice : {{ $profitPct }}%"
                >
                    @if($profitPct > 5) {{ $profitPct }}% @endif
                </div>
            </div>

            {{-- Légende --}}
            <div class="flex flex-wrap gap-4 text-sm">
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-orange-400 inline-block"></span>
                    <span class="text-gray-600 dark:text-gray-400">
                        Achats ({{ $purchasePct }}%)
                    </span>
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-red-400 inline-block"></span>
                    <span class="text-gray-600 dark:text-gray-400">
                        Dépenses ({{ $expensePct }}%)
                    </span>
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-green-400 inline-block"></span>
                    <span class="text-gray-600 dark:text-gray-400">
                        Bénéfice ({{ $profitPct }}%)
                    </span>
                </span>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════ --}}
    {{-- GRAPHIQUE ÉVOLUTION 6 MOIS                 --}}
    {{-- ══════════════════════════════════════════ --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">
            📊 Évolution du bénéfice (6 derniers mois)
        </h3>

        @php
            $evolution = $this->getProfitEvolution();
            $maxValue  = max(array_map('abs', $evolution['profits']) ?: [1]);
        @endphp

        <div class="flex items-end gap-2 h-40">
            @foreach($evolution['profits'] as $index => $profit)
                @php
                    $height  = $maxValue > 0 ? abs($profit / $maxValue) * 100 : 0;
                    $isPositive = $profit >= 0;
                @endphp

                <div class="flex-1 flex flex-col items-center gap-1">
                    {{-- Valeur --}}
                    <span class="text-xs font-medium {{ $isPositive ? 'text-green-600' : 'text-red-500' }}">
                        {{ $profit >= 0 ? '+' : '' }}{{ number_format($profit / 1000, 0) }}k
                    </span>

                    {{-- Barre --}}
                    <div class="w-full flex items-end justify-center"
                         style="height: 100px">
                        <div
                            class="w-full rounded-t-lg transition-all duration-500
                                   {{ $isPositive ? 'bg-green-400 dark:bg-green-500' : 'bg-red-400 dark:bg-red-500' }}"
                            style="height: {{ max(4, $height) }}%"
                        ></div>
                    </div>

                    {{-- Label mois --}}
                    <span class="text-xs text-gray-400 dark:text-gray-500 text-center">
                        {{ $evolution['labels'][$index] }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ══════════════════════════════════════════ --}}
    {{-- DÉTAILS : Ventes + Dépenses               --}}
    {{-- ══════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Ventes par jour --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">
                💰 Ventes par jour
            </h3>

            @php $salesByDay = $this->getSalesByDay(); @endphp

            @if($salesByDay->isEmpty())
                <div class="text-center py-8 text-gray-400 dark:text-gray-500">
                    <p class="text-sm">Aucune vente sur cette période</p>
                </div>
            @else
                <div class="space-y-2 max-h-64 overflow-y-auto">
                    @foreach($salesByDay as $day)
                        <div class="flex items-center justify-between
                                    p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ \Carbon\Carbon::parse($day->date)->translatedFormat('l d/m/Y') }}
                                </p>
                                <p class="text-xs text-gray-400">
                                    {{ $day->count }} vente(s)
                                </p>
                            </div>
                            <span class="font-bold text-blue-600 dark:text-blue-400">
                                {{ number_format($day->total, 0, ',', ' ') }} KMF
                            </span>
                        </div>
                    @endforeach
                </div>

                {{-- Sous-total --}}
                <div class="mt-3 pt-3 border-t dark:border-gray-700
                            flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                        Total
                    </span>
                    <span class="font-bold text-blue-600 dark:text-blue-400">
                        {{ number_format($this->getTotalSales(), 0, ',', ' ') }} KMF
                    </span>
                </div>
            @endif
        </div>

        {{-- Dépenses par catégorie --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">
                💳 Dépenses par catégorie
            </h3>

            @php $expensesByCat = $this->getExpensesByCategory(); @endphp

            @if($expensesByCat->isEmpty())
                <div class="text-center py-8 text-gray-400 dark:text-gray-500">
                    <p class="text-sm">Aucune dépense sur cette période</p>
                </div>
            @else
                @php $maxExp = $expensesByCat->max('total'); @endphp

                <div class="space-y-3 max-h-64 overflow-y-auto">
                    @foreach($expensesByCat as $cat)
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
                            <div class="w-full bg-gray-100 dark:bg-gray-700
                                        rounded-full h-1.5">
                                <div
                                    class="h-1.5 rounded-full"
                                    style="
                                        width: {{ $maxExp > 0 ? ($cat['total'] / $maxExp) * 100 : 0 }}%;
                                        background-color: {{ $cat['color'] }};
                                    "
                                ></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Sous-total --}}
                <div class="mt-3 pt-3 border-t dark:border-gray-700
                            flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                        Total
                    </span>
                    <span class="font-bold text-red-600 dark:text-red-400">
                        {{ number_format($this->getTotalExpenses(), 0, ',', ' ') }} KMF
                    </span>
                </div>
            @endif
        </div>
    </div>

    {{-- ══════════════════════════════════════════ --}}
    {{-- RÉSUMÉ FINAL                               --}}
    {{-- ══════════════════════════════════════════ --}}
    <div @class([
        'rounded-xl shadow p-6',
        'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800'
            => $this->getNetProfit() >= 0,
        'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800'
            => $this->getNetProfit() < 0,
    ])>
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 text-center">
            Résumé — {{ $this->getPeriodDates()['label'] }}
        </h3>

        <div class="max-w-md mx-auto space-y-2 text-sm">

            {{-- Ventes --}}
            <div class="flex justify-between items-center py-1">
                <span class="text-gray-600 dark:text-gray-400">💰 CA (Ventes)</span>
                <span class="font-semibold text-blue-600 dark:text-blue-400">
                    + {{ number_format($this->getTotalSales(), 0, ',', ' ') }} KMF
                </span>
            </div>

            {{-- Achats --}}
            <div class="flex justify-between items-center py-1">
                <span class="text-gray-600 dark:text-gray-400">🛍️ Achats (marchandises)</span>
                <span class="font-semibold text-orange-500 dark:text-orange-400">
                    - {{ number_format($this->getTotalPurchases(), 0, ',', ' ') }} KMF
                </span>
            </div>

            {{-- Dépenses --}}
            <div class="flex justify-between items-center py-1">
                <span class="text-gray-600 dark:text-gray-400">💳 Dépenses (charges)</span>
                <span class="font-semibold text-red-500 dark:text-red-400">
                    - {{ number_format($this->getTotalExpenses(), 0, ',', ' ') }} KMF
                </span>
            </div>

            {{-- Séparateur --}}
            <div class="border-t-2 dark:border-gray-600 my-2"></div>

            {{-- Bénéfice net --}}
            <div class="flex justify-between items-center py-2">
                <span class="font-bold text-gray-900 dark:text-white text-base">
                    📈 Bénéfice net estimé
                </span>
                <span @class([
                    'font-bold text-xl',
                    'text-green-600 dark:text-green-400' => $this->getNetProfit() >= 0,
                    'text-red-600 dark:text-red-400'     => $this->getNetProfit() < 0,
                ])>
                    {{ $this->getNetProfit() >= 0 ? '+' : '' }}
                    {{ number_format($this->getNetProfit(), 0, ',', ' ') }} KMF
                </span>
            </div>

            {{-- Marge --}}
            <div class="text-center">
                <span @class([
                    'text-xs px-3 py-1 rounded-full font-medium',
                    'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                        => $this->getNetProfit() >= 0,
                    'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                        => $this->getNetProfit() < 0,
                ])>
                    Marge : {{ $this->getMarginPercent() }}%
                </span>
            </div>

            {{-- Message contextuel --}}
            <p class="text-center text-xs text-gray-400 dark:text-gray-500 mt-2">
                ⚠️ Estimation basée sur les données saisies dans KomorShop
            </p>
        </div>
    </div>

</x-filament-panels::page>