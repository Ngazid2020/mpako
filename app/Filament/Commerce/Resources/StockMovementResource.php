<?php

namespace App\Filament\Commerce\Resources;

use App\Filament\Commerce\Resources\StockMovementResource\Pages;
use App\Models\StockMovement;
use App\Models\Product;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static ?string $navigationIcon   = 'heroicon-o-arrows-up-down';
    protected static ?string $navigationGroup  = 'Stock';
    protected static ?string $modelLabel       = 'Mouvement de stock';
    protected static ?string $pluralModelLabel = 'Mouvements de stock';
    protected static ?int    $navigationSort   = 4;

    public static function form(Form $form): Form
    {
        $shop = Filament::getTenant();

        return $form
            ->schema([
                Forms\Components\Section::make('Mouvement de stock')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label('Produit')
                            ->options(
                                fn () => $shop->products()
                                    ->where('is_active', true)
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->required()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Afficher le stock actuel après sélection
                                if ($state) {
                                    $product = Product::find($state);
                                    $set('current_stock', $product?->stock_qty ?? 0);
                                }
                            }),

                        // Stock actuel affiché (lecture seule)
                        Forms\Components\TextInput::make('current_stock')
                            ->label('Stock actuel')
                            ->disabled()
                            ->dehydrated(false) // Ne pas sauvegarder en BDD
                            ->suffix('unités'),

                        Forms\Components\Select::make('type')
                            ->label('Type de mouvement')
                            ->options([
                                'in'         => '📥 Entrée de stock',
                                'out'        => '📤 Sortie de stock',
                                'adjustment' => '🔧 Ajustement d\'inventaire',
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('quantity')
                            ->label('Quantité')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->helperText('Pour un ajustement, entrez la quantité RÉELLE en stock'),

                        Forms\Components\Textarea::make('reason')
                            ->label('Motif')
                            ->placeholder('Ex: Livraison fournisseur, Perte, Inventaire...')
                            ->rows(2),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Produit')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'in'         => '📥 Entrée',
                        'out'        => '📤 Sortie',
                        'adjustment' => '🔧 Ajustement',
                        default      => $state,
                    })
                    ->color(fn (string $state): string => match($state) {
                        'in'         => 'success',
                        'out'        => 'danger',
                        'adjustment' => 'warning',
                        default      => 'gray',
                    }),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantité'),

                Tables\Columns\TextColumn::make('stock_before')
                    ->label('Avant'),

                Tables\Columns\TextColumn::make('stock_after')
                    ->label('Après'),

                Tables\Columns\TextColumn::make('reason')
                    ->label('Motif')
                    ->limit(30),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Par')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'in'         => 'Entrée',
                        'out'        => 'Sortie',
                        'adjustment' => 'Ajustement',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            // Les mouvements ne se modifient pas → traçabilité
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListStockMovements::route('/'),
            'create' => Pages\CreateStockMovement::route('/create'),
        ];
    }
}