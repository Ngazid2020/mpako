<x-filament-panels::page>

    {{-- ── Sélecteur de période ── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 flex flex-wrap items-center gap-4">

        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Période :</span>

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
                        'bg-primary-600 text-white'                                                                         => $period === $value,
                        'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-primary-50 dark:hover:bg-primary-900/20' => $period !== $value,
                    ])
                >{{ $label }}</button>
            @endforeach
        </div>

        @if($period === 'month')
            <input type="month" wire:model.live="month"
                   class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-1.5 px-3"
                   max="{{ now()->format('Y-m') }}" />
        @endif

        @if($period === 'year')
            <select wire:model.live="year"
                    class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-1.5 px-3">
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
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">💰 Chiffre d'affaires</div>
            <div class="text-2xl font-bold text-gray-800 dark:text-white">
                {{ number_format($stats['total'], 0, ',', ' ') }} KMF
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">🧾 Ventes validées</div>
            <div class="text-2xl font-bold text-gray-800 dark:text-white">{{ $stats['count'] }}</div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">📊 Ticket moyen</div>
            <div class="text-2xl font-bold text-gray-800 dark:text-white">
                {{ number_format($stats['avg'], 0, ',', ' ') }} KMF
            </div>
        </div>

    </div>

    {{-- ── Graphique — wire:key force le re-mount Alpine quand la période change ── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5">
        <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">
            Évolution des ventes — {{ $this->getPeriodDates()['label'] }}
        </div>

        {{-- wire:key change → Alpine remonte → x-init relance le chart avec les nouvelles données --}}
        <div wire:key="chart-sales-{{ $period }}-{{ $month }}-{{ $year }}"
             x-data
             x-init="
                 (function() {
                     var tryRender = function() {
                         if (!window.Chart) { setTimeout(tryRender, 80); return; }
                         var el = document.getElementById('chart-sales');
                         if (!el) return;
                         var cfg = JSON.parse(el.getAttribute('data-config'));
                         if (el._ch) el._ch.destroy();
                         el._ch = new Chart(el, cfg);
                     };
                     tryRender();
                 })();
             ">
            <canvas id="chart-sales"
                    data-config="{{ json_encode($this->getChartConfig(), JSON_HEX_QUOT | JSON_HEX_APOS) }}"
                    style="max-height:260px">
            </canvas>
        </div>
    </div>

    {{-- ── Table ── --}}
    {{ $this->table }}

    {{-- Chart.js — chargé une fois, idempotent --}}
    <script>
    if (!window._chartJsLoaded) {
        window._chartJsLoaded = true;
        var _s = document.createElement('script');
        _s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js';
        document.head.appendChild(_s);
    }
    </script>

</x-filament-panels::page>
