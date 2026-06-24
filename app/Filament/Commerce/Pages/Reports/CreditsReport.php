<?php

namespace App\Filament\Commerce\Pages\Reports;

use App\Models\Credit;
use App\Traits\HasShieldPermissionPages;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CreditsReport extends Page implements HasTable
{
    use HasShieldPermissionPages;
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'Crédits en cours';
    protected static ?string $navigationIcon  = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Rapports';
    protected static ?int    $navigationSort  = 12;
    protected static ?string $title           = '💳 Crédits en cours';

    protected static string $view = 'filament.commerce.pages.reports.credits-report';

    public function table(Table $table): Table
    {
        $shop = Filament::getTenant();

        return $table
            ->query(
                Credit::query()
                    ->where('shop_id', $shop->id)
                    ->whereIn('status', ['pending', 'partial'])
                    ->with('customer')
            )
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Échéance')
                    ->date('d/m/Y')
                    ->sortable()
                    ->default('—')
                    ->color(fn ($record) => $record->isOverdue() ? 'danger' : null),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Montant')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 0, ',', ' ') . ' KMF')
                    ->sortable(),

                Tables\Columns\TextColumn::make('paid_amount')
                    ->label('Payé')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 0, ',', ' ') . ' KMF')
                    ->sortable()
                    ->color('success'),

                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('Restant dû')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 0, ',', ' ') . ' KMF')
                    ->sortable()
                    ->color('danger')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'warning',
                        'partial' => 'info',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'Non payé',
                        'partial' => 'Partiel',
                        default   => ucfirst($state),
                    }),

                Tables\Columns\IconColumn::make('overdue')
                    ->label('En retard')
                    ->getStateUsing(fn ($record) => $record->isOverdue())
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-circle')
                    ->falseIcon('heroicon-m-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending' => 'Non payé',
                        'partial' => 'Partiel',
                    ])
                    ->native(false),

                Tables\Filters\Filter::make('overdue')
                    ->label('En retard seulement')
                    ->query(fn (Builder $query) => $query->where('due_date', '<', now()))
                    ->toggle(),
            ])
            ->defaultSort('remaining_amount', 'desc')
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
                    'type' => 'credits',
                ]))
                ->openUrlInNewTab(),
        ];
    }

    public function getChartData(): array
    {
        $shop = Filament::getTenant();

        $items = $shop->credits()
            ->whereIn('status', ['pending', 'partial'])
            ->with('customer')
            ->get()
            ->groupBy('customer_id')
            ->map(fn ($rows) => [
                'name'  => $rows->first()->customer?->name ?? 'Inconnu',
                'value' => round((float) $rows->sum('remaining_amount')),
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
                    'backgroundColor' => 'rgba(239, 68, 68, 0.8)',
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

    public function getStats(): array
    {
        $shop = Filament::getTenant();

        $active = $shop->credits()->whereIn('status', ['pending', 'partial']);

        $totalDue     = (float) $active->sum('remaining_amount');
        $debtors      = (int)   $active->distinct('customer_id')->count('customer_id');
        $overdueCount = (int)   $shop->credits()
            ->whereIn('status', ['pending', 'partial'])
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->startOfDay())
            ->count();

        return [
            'total_due'     => $totalDue,
            'debtors'       => $debtors,
            'overdue_count' => $overdueCount,
        ];
    }
}
