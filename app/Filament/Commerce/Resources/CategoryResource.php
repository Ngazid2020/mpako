<?php

namespace App\Filament\Commerce\Resources;

use App\Filament\Commerce\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Facades\Filament;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon  = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Stock';
    protected static ?string $modelLabel      = 'Catégorie';
    protected static ?string $pluralModelLabel = 'Catégories';
    protected static ?int    $navigationSort  = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom de la catégorie')
                            ->placeholder('Ex: Boissons, Alimentaire, Hygiène...')
                            ->required()
                            ->maxLength(255)
                            ->autofocus(), // Focus automatique à l'ouverture

                        Forms\Components\ColorPicker::make('color')
                            ->label('Couleur')
                            ->default('#6366f1'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ColorColumn::make('color')
                    ->label(''),

                Tables\Columns\TextColumn::make('name')
                    ->label('Catégorie')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('products_count')
                    ->label('Produits')
                    ->counts('products')
                    ->badge()
                    ->color('info'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Statut'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->slideOver(), // S'ouvre en panneau latéral = plus rapide !
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
            'index'  => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            // Pas de page edit séparée → on utilise slideOver dans le tableau
        ];
    }
}