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
    @php $stats = $this->getStats(); $rows = $this->getTableData(); @endphp
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5">
            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wide">Produits vendus</div>
            <div class="text-2xl font-bold text-gray-800 dark:text-white">{{ $stats['product_count'] }}</div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5">
            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wide">CA généré</div>
            <div class="text-2xl font-bold text-violet-600 dark:text-violet-400">
                {{ number_format($stats['total_revenue'], 0, ',', ' ') }} KMF
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 sm:col-span-2">
            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wide">Meilleure vente</div>
            <div class="text-xl font-bold text-gray-800 dark:text-white truncate">{{ $stats['top_product'] }}</div>
            @if($stats['top_revenue'] > 0)
                <div class="text-sm text-violet-500 mt-0.5">{{ number_format($stats['top_revenue'], 0, ',', ' ') }} KMF</div>
            @endif
        </div>

    </div>

    {{-- ── Graphique ── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5">
        <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">
            Top 10 par chiffre d'affaires — {{ $this->getPeriodDates()['label'] }}
        </div>
        <div wire:key="chart-top-{{ $period }}-{{ $month }}-{{ $year }}"
             x-data
             x-init="(function(){
                 var tryRender = function() {
                     if (!window.Chart) { setTimeout(tryRender, 80); return; }
                     var el = document.getElementById('chart-top-products');
                     if (!el) return;
                     var cfg = JSON.parse(el.getAttribute('data-config'));
                     if (el._ch) el._ch.destroy();
                     el._ch = new Chart(el, cfg);
                 };
                 tryRender();
             })();">
            <canvas id="chart-top-products"
                    data-config="{{ json_encode($this->getChartConfig(), JSON_HEX_QUOT | JSON_HEX_APOS) }}"
                    style="max-height:340px">
            </canvas>
        </div>
    </div>

    {{-- ── Table personnalisée ── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-200 dark:border-gray-700">
            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                Classement des {{ $rows->count() }} produits les plus vendus
            </span>
        </div>
        @if($rows->isEmpty())
            <div class="px-5 py-10 text-center text-sm text-gray-400 dark:text-gray-500">
                Aucune vente sur cette période.
            </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700/50 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        <th class="px-4 py-3 text-left w-8">#</th>
                        <th class="px-4 py-3 text-left">Produit</th>
                        <th class="px-4 py-3 text-left">Catégorie</th>
                        <th class="px-4 py-3 text-right">Qté vendue</th>
                        <th class="px-4 py-3 text-right">Chiffre d'affaires</th>
                        <th class="px-4 py-3 text-right">Bénéfice</th>
                        <th class="px-4 py-3 text-right">Marge</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($rows as $i => $row)
                    @php
                        $margin = $row->revenue > 0 ? round($row->profit / $row->revenue * 100, 1) : 0;
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                        <td class="px-4 py-3 text-gray-400 font-mono text-xs">{{ $i + 1 }}</td>
                        <td class="px-4 py-3 font-medium text-gray-800 dark:text-white">{{ $row->product_name }}</td>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $row->category_name ?? '—' }}</td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">
                            {{ number_format($row->qty_sold, 2, ',', ' ') }}
                            @if($row->unit) <span class="text-xs text-gray-400">{{ $row->unit }}</span> @endif
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-violet-600 dark:text-violet-400">
                            {{ number_format($row->revenue, 0, ',', ' ') }} KMF
                        </td>
                        <td class="px-4 py-3 text-right @if($row->profit >= 0) text-emerald-600 dark:text-emerald-400 @else text-red-500 @endif">
                            {{ number_format($row->profit, 0, ',', ' ') }} KMF
                        </td>
                        <td class="px-4 py-3 text-right text-xs font-medium @if($margin >= 0) text-emerald-500 @else text-red-500 @endif">
                            {{ $margin }} %
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    <script>
    if (!window._chartJsLoaded) {
        window._chartJsLoaded = true;
        var _s = document.createElement('script');
        _s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js';
        document.head.appendChild(_s);
    }
    </script>

</x-filament-panels::page>
