<x-filament-panels::page>

    @php $stats = $this->getStats(); @endphp

    {{-- ── KPI ── --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Total dû aux fournisseurs</div>
            <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">
                {{ number_format($stats['total_debt'], 0, ',', ' ') }} KMF
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Fournisseurs créditeurs</div>
            <div class="text-2xl font-bold text-gray-800 dark:text-white">{{ $stats['supplier_count'] }}</div>
        </div>

        <div @class([
            'rounded-xl shadow p-5',
            'bg-amber-50 dark:bg-amber-900/20 border-l-4 border-amber-400' => $stats['purchase_count'] > 0,
            'bg-white dark:bg-gray-800'                                     => $stats['purchase_count'] === 0,
        ])>
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Achats non soldés</div>
            <div @class([
                'text-2xl font-bold',
                'text-amber-600 dark:text-amber-400' => $stats['purchase_count'] > 0,
                'text-gray-800 dark:text-white'      => $stats['purchase_count'] === 0,
            ])>{{ $stats['purchase_count'] }}</div>
        </div>

    </div>

    {{-- ── Graphique ── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5" wire:ignore>
        <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">
            Top fournisseurs — restant dû
        </div>
        <canvas id="chart-supplier-debts" style="max-height:300px"></canvas>
    </div>

    {{-- ── Table ── --}}
    {{ $this->table }}

    <script>
    (function () {
        var config = @json($this->getChartConfig());
        var tryRender = function () {
            if (!window.Chart) { setTimeout(tryRender, 80); return; }
            var el = document.getElementById('chart-supplier-debts');
            if (!el) return;
            if (el._ch) el._ch.destroy();
            el._ch = new Chart(el, config);
        };
        tryRender();
    })();
    </script>

    <script>
    if (!window._chartJsLoaded) {
        window._chartJsLoaded = true;
        var _s = document.createElement('script');
        _s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js';
        document.head.appendChild(_s);
    }
    </script>

</x-filament-panels::page>
