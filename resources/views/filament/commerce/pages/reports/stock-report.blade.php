<x-filament-panels::page>

    @php $stats = $this->getStats(); @endphp

    {{-- ── KPI ── --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">💰 Valeur totale du stock</div>
            <div class="text-2xl font-bold text-gray-800 dark:text-white">
                {{ number_format($stats['total_value'], 0, ',', ' ') }} KMF
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">📦 Total produits actifs</div>
            <div class="text-2xl font-bold text-gray-800 dark:text-white">{{ $stats['total_products'] }}</div>
        </div>

        <div @class([
            'rounded-xl shadow p-5',
            'bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400' => $stats['alert_count'] > 0,
            'bg-white dark:bg-gray-800'                               => $stats['alert_count'] === 0,
        ])>
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">⚠️ Produits en alerte</div>
            <div @class([
                'text-2xl font-bold',
                'text-red-600 dark:text-red-400' => $stats['alert_count'] > 0,
                'text-gray-800 dark:text-white'  => $stats['alert_count'] === 0,
            ])>{{ $stats['alert_count'] }}</div>
        </div>

    </div>

    {{-- ── Graphique ── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5" wire:ignore>
        <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">
            Top produits par valeur de stock
        </div>
        <canvas id="chart-stock" style="max-height:260px"></canvas>
    </div>

    {{-- ── Table ── --}}
    {{ $this->table }}

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
    (function () {
        var config = @json($this->getChartConfig());
        function render() {
            var el = document.getElementById('chart-stock');
            if (!el || !window.Chart) return;
            if (el._ch) el._ch.destroy();
            el._ch = new Chart(el, config);
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', render);
        } else {
            render();
        }
    })();
    </script>

</x-filament-panels::page>
