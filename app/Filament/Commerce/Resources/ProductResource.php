<?php

namespace App\Filament\Commerce\Resources;

use App\Filament\Commerce\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon   = 'heroicon-o-cube';
    protected static ?string $navigationGroup  = 'Stock';
    protected static ?string $modelLabel       = 'Produit';
    protected static ?string $pluralModelLabel = 'Produits';
    protected static ?int    $navigationSort   = 1;

    public static function form(Form $form): Form
    {
        // Récupérer le shop courant (tenant)
        $shop = Filament::getTenant();

        return $form
            ->schema([
                // ── Section principale ──
                Forms\Components\Section::make('Informations du produit')
                    ->icon('heroicon-o-cube')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom du produit')
                            ->placeholder('Ex: Riz parfumé 5kg, Coca-Cola 1.5L...')
                            ->required()
                            ->maxLength(255)
                            ->autofocus()
                            ->columnSpanFull(),

                        Forms\Components\Select::make('category_id')
                            ->label('Catégorie')
                            ->options(
                                // Seulement les catégories de CE commerce
                                fn () => $shop->categories()
                                    ->where('is_active', true)
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->createOptionForm([
                                // Créer une catégorie directement depuis le select !
                                Forms\Components\TextInput::make('name')
                                    ->label('Nom de la catégorie')
                                    ->required(),
                                Forms\Components\ColorPicker::make('color')
                                    ->label('Couleur')
                                    ->default('#6366f1'),
                            ])
                            ->createOptionUsing(function (array $data) use ($shop) {
                                return $shop->categories()->create($data)->id;
                            }),

                        Forms\Components\Select::make('unit_id')
                            ->label('Unité')
                            ->options(
                                fn () => $shop->units()
                                    ->where('is_active', true)
                                    ->get()
                                    ->mapWithKeys(fn ($unit) => [
                                        $unit->id => "{$unit->name} ({$unit->abbreviation})"
                                    ])
                            )
                            ->searchable()
                            ->native(false)
                            ->createOptionForm([
                                // Créer une unité directement depuis le select !
                                Forms\Components\TextInput::make('name')
                                    ->label('Nom')
                                    ->required(),
                                Forms\Components\TextInput::make('abbreviation')
                                    ->label('Abréviation')
                                    ->required(),
                            ])
                            ->createOptionUsing(function (array $data) use ($shop) {
                                return $shop->units()->create($data)->id;
                            }),

                        Forms\Components\TextInput::make('barcode')
                            ->label('Code-barres')
                            ->placeholder('Scanner ou saisir...')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                // ── Section prix ──
                Forms\Components\Section::make('Prix (en KMF)')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Forms\Components\TextInput::make('buy_price')
                            ->label('Prix d\'achat')
                            ->numeric()
                            ->default(0)
                            ->suffix('KMF')
                            ->minValue(0)
                            ->live(debounce: 500)
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                // Suggestion automatique du prix de vente (+20%)
                                if ($state > 0 && $get('sell_price') == 0) {
                                    $set('sell_price', round($state * 1.2));
                                }
                            }),

                        Forms\Components\TextInput::make('sell_price')
                            ->label('Prix de vente')
                            ->numeric()
                            ->default(0)
                            ->suffix('KMF')
                            ->minValue(0),
                    ])
                    ->columns(2),

                // ── Section stock ──
                Forms\Components\Section::make('Stock')
                    ->icon('heroicon-o-archive-box')
                    ->schema([
                        Forms\Components\TextInput::make('stock_qty')
                            ->label('Quantité initiale')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->helperText('Stock de départ lors de la création'),

                        Forms\Components\TextInput::make('stock_alert')
                            ->label('Seuil d\'alerte')
                            ->numeric()
                            ->default(5)
                            ->minValue(0)
                            ->helperText('Alerte quand le stock descend en dessous de ce seuil'),
                    ])
                    ->columns(2),

                // ── Section statut ──
                Forms\Components\Section::make('Statut')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Produit actif')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Produit')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Product $record) => $record->category?->name),

                Tables\Columns\TextColumn::make('sell_price')
                    ->label('Prix vente')
                    ->money('KMF')
                    ->sortable(),

                Tables\Columns\TextColumn::make('buy_price')
                    ->label('Prix achat')
                    ->money('KMF')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // ⚠️ Colonne stock avec alerte visuelle
                Tables\Columns\TextColumn::make('stock_qty')
                    ->label('Stock')
                    ->sortable()
                    ->badge()
                    ->color(fn (Product $record): string => match(true) {
                        $record->stock_qty <= 0                => 'danger',   // Rouge : rupture
                        $record->stock_qty <= $record->stock_alert => 'warning', // Orange : bas
                        default                                => 'success',  // Vert : ok
                    })
                    ->formatStateUsing(fn (Product $record): string =>
                        $record->stock_qty . ' ' . ($record->unit?->abbreviation ?? '')
                    ),

                Tables\Columns\TextColumn::make('unit.abbreviation')
                    ->label('Unité')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Catégorie')
                    ->relationship('category', 'name'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Statut'),

                // Filtre "Stock bas" très utile !
                Tables\Filters\Filter::make('low_stock')
                    ->label('Stock bas uniquement')
                    ->query(fn (Builder $query) =>
                        $query->whereColumn('stock_qty', '<=', 'stock_alert')
                    )
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit'   => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}