<?php

namespace App\Filament\Commerce\Resources;

use App\Filament\Commerce\Resources\UnitResource\Pages;
use App\Models\Unit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UnitResource extends Resource
{
    protected static ?string $model = Unit::class;

    protected static ?string $navigationIcon   = 'heroicon-o-scale';
    protected static ?string $navigationGroup  = 'Stock';
    protected static ?string $modelLabel       = 'Unité';
    protected static ?string $pluralModelLabel = 'Unités';
    protected static ?int    $navigationSort   = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->placeholder('Ex: Kilogramme, Litre, Sachet...')
                            ->required()
                            ->maxLength(255)
                            ->autofocus(),

                        Forms\Components\TextInput::make('abbreviation')
                            ->label('Abréviation')
                            ->placeholder('Ex: kg, L, sct, pcs...')
                            ->required()
                            ->maxLength(10),

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
                Tables\Columns\TextColumn::make('name')
                    ->label('Unité')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('abbreviation')
                    ->label('Abréviation')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('products_count')
                    ->label('Produits')
                    ->counts('products')
                    ->badge()
                    ->color('info'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->slideOver(),
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
            'index'  => Pages\ListUnits::route('/'),
            'create' => Pages\CreateUnit::route('/create'),
        ];
    }
}