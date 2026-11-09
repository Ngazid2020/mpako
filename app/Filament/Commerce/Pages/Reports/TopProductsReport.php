<?php

namespace App\Filament\Commerce\Pages\Reports;

use App\Traits\HasShieldPermissionPages;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TopProductsReport extends Page
{
    use HasShieldPermissionPages;

    protected static ?string $navigationLabel = 'Top produits vendus';
    protected static ?string $navigationIcon  = 'heroicon-o-fire';
    protected static ?string $navigationGroup = 'Rapports';
    protected static ?int    $navigationSort  = 15;
    protected static ?string $title           = 'Top produits vendus';

    protected static string $view = 'filament.commerce.pages.reports.top-products-report';

    public string $period = 'month';
    public string $month;
    public string $year;

    private ?Collection $cache = null;

    public function mount(): void
    {
        $this->month = now()->format('Y-m');
        $this->year  = now()->format('Y');
    }

    public function getPeriodDates(): array
    {
        $now = CarbonImmutable::now();

        return match ($this->period) {
            'today' => [
                'start' => $now->startOfDay(),
                'end'   => $now->endOfDay(),
                'label' => "Aujourd'hui — " . $now->format('d/m/Y'),
            ],
            'week' => [
                'start' => $now->startOfWeek(),
                'end'   => $now->endOfWeek(),
                'label' => 'Cette semaine — ' . $now->startOfWeek()->format('d/m') . ' au ' . $now->endOfWeek()->format('d/m/Y'),
            ],
            'year' => [
                'start' => CarbonImmutable::parse($this->year)->startOfYear(),
                'end'   => CarbonImmutable::parse($this->year)->endOfYear(),
                'label' => 'Année ' . $this->year,
            ],
            default => [
                'start' => CarbonImmutable::parse($this->month)->startOfMonth(),
                'end'   => CarbonImmutable::parse($this->month)->endOfMonth(),
                'label' => CarbonImmutable::parse($this->month)->translatedFormat('F Y'),
            ],
        };
    }

    public function getTableData(): Collection
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $shop  = Filament::getTenant();
        $dates = $this->getPeriodDates();

        return $this->cache = DB::table('sale_items as si')
            ->join('sales as s',    's.id', '=', 'si.sale_id')
            ->join('products as p', 'p.id', '=', 'si.product_id')
            ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
            ->leftJoin('units as u',      'u.id', '=', 'p.unit_id')
            ->where('s.shop_id', $shop->id)
            ->where('s.status', 'completed')
            ->whereBetween('s.created_at', [
                $dates['start']->startOfDay(),
                $dates['end']->endOfDay(),
            ])
            ->groupBy('si.product_id', 'p.name', 'c.name', 'u.abbreviation', 'p.buy_price', 'p.sell_price')
            ->selectRaw('
                p.name            AS product_name,
                c.name            AS category_name,
                u.abbreviation    AS unit,
                p.buy_price,
                SUM(si.quantity)  AS qty_sold,
                SUM(si.subtotal)  AS revenue,
                SUM(si.subtotal - si.quantity * p.buy_price) AS profit
            ')
            ->orderByDesc('revenue')
            ->limit(50)
            ->get();
    }

    public function getStats(): array
    {
        $data = $this->getTableData();
        $top  = $data->first();

        return [
            'product_count' => $data->count(),
            'total_revenue' => (float) $data->sum('revenue'),
            'top_product'   => $top?->product_name ?? '—',
            'top_revenue'   => (float) ($top?->revenue ?? 0),
        ];
    }

    public function getChartConfig(): array
    {
        $top = $this->getTableData()->take(10);

        return [
            'type' => 'bar',
            'data' => [
                'labels'   => $top->pluck('product_name')->toArray(),
                'datasets' => [[
                    'label'           => 'CA (KMF)',
                    'data'            => $top->pluck('revenue')->map(fn ($v) => round((float) $v))->toArray(),
                    'backgroundColor' => 'rgba(139, 92, 246, 0.85)',
                    'borderColor'     => 'rgb(109, 40, 217)',
                    'borderRadius'    => 4,
                ]],
            ],
            'options' => [
                'indexAxis'           => 'y',
                'responsive'          => true,
                'maintainAspectRatio' => true,
                'plugins'             => ['legend' => ['display' => false]],
                'scales'              => ['x' => ['beginAtZero' => true]],
            ],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')
                ->label('Télécharger PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(fn () => route('commerce.reports.pdf', [
                    'shop'   => Filament::getTenant()->slug,
                    'type'   => 'top-produits',
                    'period' => $this->period,
                    'month'  => $this->month,
                    'year'   => $this->year,
                ]))
                ->openUrlInNewTab(),
        ];
    }
}
