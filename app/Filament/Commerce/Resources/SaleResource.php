<?php

namespace App\Filament\Commerce\Resources;

use App\Filament\Commerce\Resources\SaleResource\Pages;
use App\Models\Sale;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static ?string $navigationIcon   = 'heroicon-o-receipt-percent';
    protected static ?string $navigationGroup  = 'Ventes';
    protected static ?string $modelLabel       = 'Vente';
    protected static ?string $pluralModelLabel = 'Historique des ventes';
    protected static ?int    $navigationSort   = 1;

    // Pas de création depuis la resource → on passe par la page Caisse
    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Vente')
                    ->schema([
                        Infolists\Components\TextEntry::make('reference')
                            ->label('Référence')
                            ->weight('bold')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('status')
                            ->label('Statut')
                            ->badge()
                            ->formatStateUsing(fn ($state) => match($state) {
                                'completed' => '✅ Complétée',
                                'cancelled' => '❌ Annulée',
                                default     => $state,
                            })
                            ->color(fn ($state) => match($state) {
                                'completed' => 'success',
                                'cancelled' => 'danger',
                                default     => 'gray',
                            }),

                        Infolists\Components\TextEntry::make('total_amount')
                            ->label('Total')
                            ->money('KMF')
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('paid_amount')
                            ->label('Payé')
                            ->money('KMF'),

                        Infolists\Components\TextEntry::make('change_amount')
                            ->label('Monnaie rendue')
                            ->money('KMF'),

                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Caissier'),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Date')
                            ->dateTime('d/m/Y H:i'),

                        Infolists\Components\TextEntry::make('note')
                            ->label('Note')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                // Lignes de la vente
                Infolists\Components\Section::make('Produits vendus')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('product_name')
                                    ->label('Produit')
                                    ->weight('bold'),

                                Infolists\Components\TextEntry::make('quantity')
                                    ->label('Qté'),

                                Infolists\Components\TextEntry::make('unit_price')
                                    ->label('Prix unit.')
                                    ->money('KMF'),

                                Infolists\Components\TextEntry::make('subtotal')
                                    ->label('Sous-total')
                                    ->money('KMF')
                                    ->weight('bold'),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->weight('bold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('KMF')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('paid_amount')
                    ->label('Payé')
                    ->money('KMF')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('change_amount')
                    ->label('Monnaie')
                    ->money('KMF')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'completed' => '✅ Complétée',
                        'cancelled' => '❌ Annulée',
                        default     => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default     => 'gray',
                    }),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Caissier')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'completed' => 'Complétées',
                        'cancelled' => 'Annulées',
                    ]),

                Tables\Filters\Filter::make('today')
                    ->label("Aujourd'hui")
                    ->query(fn ($query) => $query->whereDate('created_at', today()))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSales::route('/'),
            'view'  => Pages\ViewSale::route('/{record}'),
        ];
    }
}