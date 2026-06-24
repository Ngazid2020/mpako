<x-filament-panels::page>

    {{-- ── Sélecteur de période ── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 flex flex-wrap items-center gap-4">
        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Période :</span>
        <div class="flex gap-2 flex-wrap">
            @foreach(['today' => "Aujourd'hui", 'week' => 'Cette semaine', 'month' => 'Par mois', 'year' => 'Par année'] as $value => $label)
                <button wire:click="$set('period', '{{ $value }}')"
                    @class([
                        'px-3 py-1.5 rounded-lg text-sm font-medium transition-colors',
                        'bg-primary-600 text-white'                                                                                        => $period === $value,
                        'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-primary-50 dark:hover:bg-primary-900/20'  => $period !== $value,
                    ])>{{ $label }}</button>
            @endforeach
        </div>
        @if($period === 'month')
            <input type="month" wire:model.live="month"
                   class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-1.5 px-3"
                   max="{{ now()->format('Y-m') }}">
        @endif
        @if($period === 'year')
            <select wire:model.live="year" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-1.5 px-3">
                @for($y = now()->year; $y >= now()->year - 3; $y--)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endfor
            </select>
        @endif
        <span class="ml-auto text-sm font-medium text-primary-600 dark:text-primary-400">
            {{ $this->getPeriodDates()['label'] }}
        </span>
    </div>

    {{-- ── KPI ── --}}
    @php $stats = $this->getStats(); @endphp
    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-4">

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5">
            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wide">Chiffre d'affaires</div>
            <div class="text-xl font-bold text-emerald-600 dark:text-emerald-400">
                {{ number_format($stats['revenue'], 0, ',', ' ') }} KMF
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5">
            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wide">Coût achats</div>
            <div class="text-xl font-bold text-red-500 dark:text-red-400">
                {{ number_format($stats['purchaseCost'], 0, ',', ' ') }} KMF
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5">
            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wide">Dépenses</div>
            <div class="text-xl font-bold text-amber-500 dark:text-amber-400">
                {{ number_format($stats['expenses'], 0, ',', ' ') }} KMF
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5">
            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wide">Bénéfice brut</div>
            <div @class(['text-xl font-bold', 'text-emerald-600 dark:text-emerald-400' => $stats['grossProfit'] >= 0, 'text-red-600 dark:text-red-400' => $stats['grossProfit'] < 0])>
                {{ number_format($stats['grossProfit'], 0, ',', ' ') }} KMF
            </div>
        </div>

        <div @class(['rounded-xl shadow p-5', $stats['netProfit'] >= 0 ? 'bg-emerald-50 dark:bg-emerald-900/20 border-l-4 border-emerald-400' : 'bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400'])>
            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wide">Résultat net</div>
            <div @class(['text-xl font-bold', 'text-emerald-700 dark:text-emerald-300' => $stats['netProfit'] >= 0, 'text-red-700 dark:text-red-300' => $stats['netProfit'] < 0])>
                {{ number_format($stats['netProfit'], 0, ',', ' ') }} KMF
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5">
            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wide">Marge brute</div>
            <div @class(['text-xl font-bold', 'text-emerald-600 dark:text-emerald-400' => $stats['margin'] >= 0, 'text-red-600 dark:text-red-400' => $stats['margin'] < 0])>
                {{ $stats['margin'] }} %
            </div>
        </div>

    </div>

    {{-- ── Graphique ── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5">
        <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">
            Synthèse — {{ $this->getPeriodDates()['label'] }}
        </div>
        <div wire:key="chart-profit-{{ $period }}-{{ $month }}-{{ $year }}"
             x-data
             x-init="(function(){
                 var tryRender = function() {
                     if (!window.Chart) { setTimeout(tryRender, 80); return; }
                     var el = document.getElementById('chart-profit');
                     if (!el) return;
                     var cfg = JSON.parse(el.getAttribute('data-config'));
                     if (el._ch) el._ch.destroy();
                     el._ch = new Chart(el, cfg);
                 };
                 tryRender();
             })();">
            <canvas id="chart-profit"
                    data-config="{{ json_encode($this->getChartConfig($stats), JSON_HEX_QUOT | JSON_HEX_APOS) }}"
                    style="max-height:260px">
            </canvas>
        </div>
    </div>

    {{-- ── Table des dépenses ── --}}
    <div class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide -mb-2">
        Détail des dépenses sur la période
    </div>
    {{ $this->table }}

    <script>
    if (!window._chartJsLoaded) {
        window._chartJsLoaded = true;
        var _s = document.createElement('script');
        _s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js';
        document.head.appendChild(_s);
    }
    </script>

</x-filament-panels::page>
