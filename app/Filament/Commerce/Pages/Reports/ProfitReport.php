<?php

namespace App\Filament\Commerce\Pages\Reports;

use App\Models\Expense;
use App\Models\Purchase;
use App\Models\Sale;
use App\Traits\HasShieldPermissionPages;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ProfitReport extends Page implements HasTable
{
    use HasShieldPermissionPages;
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'Résultat net';
    protected static ?string $navigationIcon  = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Rapports';
    protected static ?int    $navigationSort  = 13;
    protected static ?string $title           = 'Résultat net';

    protected static string $view = 'filament.commerce.pages.reports.profit-report';

    public string $period = 'month';
    public string $month;
    public string $year;

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

    public function table(Table $table): Table
    {
        $shop  = Filament::getTenant();
        $dates = $this->getPeriodDates();

        return $table
            ->query(
                Expense::query()
                    ->where('shop_id', $shop->id)
                    ->whereBetween('spent_at', [$dates['start'], $dates['end']])
                    ->with('category')
            )
            ->columns([
                Tables\Columns\TextColumn::make('spent_at')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Catégorie')
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(60)
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 0, ',', ' ') . ' KMF')
                    ->sortable()
                    ->color('danger'),
            ])
            ->defaultSort('spent_at', 'desc')
            ->paginated([25, 50, 100]);
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
                    'type'   => 'profit',
                    'period' => $this->period,
                    'month'  => $this->month,
                    'year'   => $this->year,
                ]))
                ->openUrlInNewTab(),
        ];
    }

    public function getStats(): array
    {
        $shop  = Filament::getTenant();
        $dates = $this->getPeriodDates();

        $revenue = (float) Sale::query()
            ->where('shop_id', $shop->id)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$dates['start']->startOfDay(), $dates['end']->endOfDay()])
            ->sum('total_amount');

        $purchaseCost = (float) Purchase::query()
            ->where('shop_id', $shop->id)
            ->where('status', 'completed')
            ->whereBetween('purchased_at', [$dates['start'], $dates['end']])
            ->sum('total_amount');

        $expenses = (float) Expense::query()
            ->where('shop_id', $shop->id)
            ->whereBetween('spent_at', [$dates['start'], $dates['end']])
            ->sum('amount');

        $grossProfit = $revenue - $purchaseCost;
        $netProfit   = $grossProfit - $expenses;
        $margin      = $revenue > 0 ? round(($grossProfit / $revenue) * 100, 1) : 0;

        return compact('revenue', 'purchaseCost', 'expenses', 'grossProfit', 'netProfit', 'margin');
    }

    public function getChartConfig(array $stats): array
    {
        return [
            'type' => 'bar',
            'data' => [
                'labels'   => ["Chiffre d'affaires", 'Cout achats', 'Depenses', 'Resultat net'],
                'datasets' => [[
                    'data'            => [
                        round($stats['revenue']),
                        round($stats['purchaseCost']),
                        round($stats['expenses']),
                        round(max(0, $stats['netProfit'])),
                    ],
                    'backgroundColor' => [
                        'rgba(16, 185, 129, 0.85)',
                        'rgba(239, 68, 68, 0.85)',
                        'rgba(245, 158, 11, 0.85)',
                        'rgba(59, 130, 246, 0.85)',
                    ],
                    'borderRadius' => 4,
                ]],
            ],
            'options' => [
                'responsive'          => true,
                'maintainAspectRatio' => true,
                'plugins'             => ['legend' => ['display' => false]],
                'scales'              => ['y' => ['beginAtZero' => true]],
            ],
        ];
    }
}
