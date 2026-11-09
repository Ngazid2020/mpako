<?php

namespace App\Filament\Commerce\Pages\Reports;

use App\Models\Purchase;
use App\Traits\HasShieldPermissionPages;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class SupplierDebtsReport extends Page implements HasTable
{
    use HasShieldPermissionPages;
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'Dettes fournisseurs';
    protected static ?string $navigationIcon  = 'heroicon-o-building-storefront';
    protected static ?string $navigationGroup = 'Rapports';
    protected static ?int    $navigationSort  = 14;
    protected static ?string $title           = 'Dettes fournisseurs';

    protected static string $view = 'filament.commerce.pages.reports.supplier-debts-report';

    public function table(Table $table): Table
    {
        $shop = Filament::getTenant();

        return $table
            ->query(
                Purchase::query()
                    ->where('shop_id', $shop->id)
                    ->where('status', 'completed')
                    ->whereIn('payment_status', ['unpaid', 'partial'])
                    ->with('supplier')
            )
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Fournisseur')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('purchased_at')
                    ->label('Date achat')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 0, ',', ' ') . ' KMF')
                    ->sortable(),

                Tables\Columns\TextColumn::make('paid_amount')
                    ->label('Payé')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 0, ',', ' ') . ' KMF')
                    ->sortable()
                    ->color('success'),

                Tables\Columns\TextColumn::make('debt_amount')
                    ->label('Restant dû')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 0, ',', ' ') . ' KMF')
                    ->sortable()
                    ->color('danger')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'unpaid'  => 'danger',
                        'partial' => 'warning',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'unpaid'  => 'Non payé',
                        'partial' => 'Partiel',
                        default   => ucfirst($state),
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Statut')
                    ->options(['unpaid' => 'Non payé', 'partial' => 'Partiel'])
                    ->native(false),

                Tables\Filters\SelectFilter::make('supplier_id')
                    ->label('Fournisseur')
                    ->relationship('supplier', 'name', fn ($query) => $query->where('shop_id', Filament::getTenant()->id))
                    ->searchable()
                    ->preload()
                    ->native(false),
            ])
            ->defaultSort('debt_amount', 'desc')
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
                    'type' => 'dettes-fournisseurs',
                ]))
                ->openUrlInNewTab(),
        ];
    }

    public function getStats(): array
    {
        $shop = Filament::getTenant();

        $row = Purchase::query()
            ->where('shop_id', $shop->id)
            ->where('status', 'completed')
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->selectRaw('SUM(debt_amount) as total_debt, COUNT(DISTINCT supplier_id) as supplier_count, COUNT(*) as purchase_count')
            ->first();

        return [
            'total_debt'     => (float) ($row->total_debt ?? 0),
            'supplier_count' => (int)   ($row->supplier_count ?? 0),
            'purchase_count' => (int)   ($row->purchase_count ?? 0),
        ];
    }

    public function getChartConfig(): array
    {
        $shop = Filament::getTenant();

        $items = Purchase::query()
            ->where('shop_id', $shop->id)
            ->where('status', 'completed')
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->with('supplier')
            ->get()
            ->groupBy('supplier_id')
            ->map(fn ($rows) => [
                'name'  => $rows->first()->supplier?->name ?? 'Inconnu',
                'value' => round((float) $rows->sum('debt_amount')),
            ])
            ->sortByDesc('value')
            ->take(10)
            ->values();

        return [
            'type' => 'bar',
            'data' => [
                'labels'   => $items->pluck('name')->toArray(),
                'datasets' => [[
                    'data'            => $items->pluck('value')->toArray(),
                    'backgroundColor' => 'rgba(245, 158, 11, 0.85)',
                    'borderColor'     => 'rgb(180, 83, 9)',
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
}
