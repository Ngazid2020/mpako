<?php

namespace App\Filament\Commerce\Pages\Reports;

use App\Models\Product;
use App\Traits\HasShieldPermissionPages;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockReport extends Page implements HasTable
{
    use HasShieldPermissionPages;
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'État du stock';
    protected static ?string $navigationIcon  = 'heroicon-o-cube';
    protected static ?string $navigationGroup = 'Rapports';
    protected static ?int    $navigationSort  = 10;
    protected static ?string $title           = '📦 État du stock';

    protected static string $view = 'filament.commerce.pages.reports.stock-report';

    public function table(Table $table): Table
    {
        $shop = Filament::getTenant();

        return $table
            ->query(
                Product::query()
                    ->where('shop_id', $shop->id)
                    ->with(['category', 'unit'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Produit')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Catégorie')
                    ->sortable()
                    ->default('—'),

                Tables\Columns\TextColumn::make('stock_qty')
                    ->label('Stock')
                    ->sortable()
                    ->formatStateUsing(fn ($state, $record) =>
                        number_format((float) $state, 2, ',', ' ') . ' ' . ($record->unit?->abbreviation ?? '')),

                Tables\Columns\TextColumn::make('buy_price')
                    ->label('PA/unité')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 0, ',', ' ') . ' KMF'),

                Tables\Columns\TextColumn::make('sell_price')
                    ->label('PV/unité')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 0, ',', ' ') . ' KMF'),

                Tables\Columns\TextColumn::make('stock_value')
                    ->label('Valeur stock')
                    ->getStateUsing(fn ($record) => $record->stock_qty * $record->buy_price)
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 0, ',', ' ') . ' KMF'),

                Tables\Columns\TextColumn::make('margin')
                    ->label('Marge')
                    ->getStateUsing(fn ($record) => $record->sell_price > 0
                        ? round((($record->sell_price - $record->buy_price) / $record->sell_price) * 100, 1)
                        : 0)
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->color(fn ($state) => $state >= 20 ? 'success' : ($state >= 0 ? 'warning' : 'danger')),

                Tables\Columns\TextColumn::make('stock_status')
                    ->label('Statut')
                    ->badge()
                    ->getStateUsing(fn ($record) => ($record->stock_qty <= ($record->stock_alert ?? 0)) ? 'Alerte' : 'OK')
                    ->color(fn ($state) => $state === 'Alerte' ? 'danger' : 'success'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Catégorie')
                    ->relationship('category', 'name')
                    ->native(false),

                Tables\Filters\Filter::make('low_stock')
                    ->label('Stock faible seulement')
                    ->query(fn (Builder $query) => $query->whereRaw('stock_qty <= stock_alert'))
                    ->toggle(),
            ])
            ->defaultSort('name')
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
                    'shop' => Filament::getTenant()->slug,
                    'type' => 'stock',
                ]))
                ->openUrlInNewTab(),
        ];
    }

    public function getChartData(): array
    {
        $shop = Filament::getTenant();

        $items = $shop->products()
            ->get()
            ->map(fn ($p) => [
                'name'  => $p->name,
                'value' => round($p->stock_qty * $p->buy_price),
            ])
            ->sortByDesc('value')
            ->take(10)
            ->values();

        return [
            'labels' => $items->pluck('name')->toArray(),
            'values' => $items->pluck('value')->toArray(),
        ];
    }

    public function getChartConfig(): array
    {
        $data = $this->getChartData();

        return [
            'type' => 'bar',
            'data' => [
                'labels'   => $data['labels'],
                'datasets' => [[
                    'data'            => $data['values'],
                    'backgroundColor' => 'rgba(59, 130, 246, 0.8)',
                    'borderRadius'    => 4,
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
        $shop = Filament::getTenant();

        $totalValue    = $shop->products()->selectRaw('SUM(stock_qty * buy_price) as val')->value('val') ?? 0;
        $alertCount    = $shop->products()->whereRaw('stock_qty <= stock_alert')->count();
        $totalProducts = $shop->products()->count();

        return [
            'total_value'    => (float) $totalValue,
            'alert_count'    => (int) $alertCount,
            'total_products' => (int) $totalProducts,
        ];
    }
}
