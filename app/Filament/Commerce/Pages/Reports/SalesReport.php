<?php

namespace App\Filament\Commerce\Pages\Reports;

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

class SalesReport extends Page implements HasTable
{
    use HasShieldPermissionPages;
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'Ventes par période';
    protected static ?string $navigationIcon  = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = 'Rapports';
    protected static ?int    $navigationSort  = 11;
    protected static ?string $title           = '🛒 Ventes par période';

    protected static string $view = 'filament.commerce.pages.reports.sales-report';

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
                'label' => 'Cette semaine — '
                    . $now->startOfWeek()->format('d/m')
                    . ' au '
                    . $now->endOfWeek()->format('d/m/Y'),
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
                Sale::query()
                    ->where('shop_id', $shop->id)
                    ->whereBetween('created_at', [
                        $dates['start']->startOfDay(),
                        $dates['end']->endOfDay(),
                    ])
                    ->withCount('items')
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('items_count')
                    ->label('Articles')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 0, ',', ' ') . ' KMF')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default     => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'completed' => 'Validée',
                        'cancelled' => 'Annulée',
                        default     => ucfirst($state),
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'completed' => 'Validées',
                        'cancelled' => 'Annulées',
                    ])
                    ->native(false),
            ])
            ->defaultSort('created_at', 'desc')
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
                    'type'   => 'ventes',
                    'period' => $this->period,
                    'month'  => $this->month,
                    'year'   => $this->year,
                ]))
                ->openUrlInNewTab(),
        ];
    }

    public function getChartData(): array
    {
        $shop  = Filament::getTenant();
        $dates = $this->getPeriodDates();

        $rows = $shop->sales()
            ->where('status', 'completed')
            ->whereBetween('created_at', [
                $dates['start']->startOfDay(),
                $dates['end']->endOfDay(),
            ])
            ->selectRaw('DATE(created_at) as day, SUM(total_amount) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        return [
            'labels' => $rows->pluck('day')
                ->map(fn ($d) => \Carbon\Carbon::parse($d)->format('d/m'))
                ->toArray(),
            'values' => $rows->pluck('total')
                ->map(fn ($v) => round((float) $v))
                ->toArray(),
        ];
    }

    public function getChartConfig(): array
    {
        $data = $this->getChartData();

        return [
            'type' => 'line',
            'data' => [
                'labels'   => $data['labels'],
                'datasets' => [[
                    'data'            => $data['values'],
                    'borderColor'     => '#0f766e',
                    'backgroundColor' => 'rgba(15, 118, 110, 0.1)',
                    'fill'            => true,
                    'tension'         => 0.4,
                    'pointRadius'     => 3,
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

    public function getStats(): array
    {
        $shop  = Filament::getTenant();
        $dates = $this->getPeriodDates();

        $query = $shop->sales()
            ->where('status', 'completed')
            ->whereBetween('created_at', [
                $dates['start']->startOfDay(),
                $dates['end']->endOfDay(),
            ]);

        $count = (int) $query->count();
        $total = (float) $query->sum('total_amount');

        return [
            'count' => $count,
            'total' => $total,
            'avg'   => $count > 0 ? round($total / $count) : 0,
        ];
    }
}
