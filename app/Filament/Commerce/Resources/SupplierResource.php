<?php

namespace App\Filament\Commerce\Resources;

use App\Filament\Commerce\Resources\SupplierResource\Pages;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $navigationIcon   = 'heroicon-o-truck';
    protected static ?string $navigationGroup  = 'Achats';
    protected static ?string $modelLabel       = 'Fournisseur';
    protected static ?string $pluralModelLabel = 'Fournisseurs';
    protected static ?int    $navigationSort   = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom du fournisseur')
                            ->placeholder('Ex: Grossiste Moroni, Ali Distribution...')
                            ->required()
                            ->maxLength(255)
                            ->autofocus(),

                        Forms\Components\TextInput::make('phone')
                            ->label('Téléphone')
                            ->placeholder('Ex: 333 00 00')
                            ->tel(),

                        Forms\Components\Textarea::make('address')
                            ->label('Adresse / Localisation')
                            ->placeholder('Ex: Volo Volo, en face du marché...')
                            ->rows(2)
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Actif')
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
                    ->label('Fournisseur')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Téléphone')
                    ->icon('heroicon-m-phone'),

                Tables\Columns\TextColumn::make('purchases_count')
                    ->label('Achats')
                    ->counts('purchases')
                    ->badge()
                    ->color('info'),

                // Balance — montant dû au fournisseur
                Tables\Columns\TextColumn::make('balance')
                    ->label('Dette')
                    ->money('KMF')
                    ->sortable()
                    ->badge()
                    ->color(fn (Supplier $record): string =>
                        $record->balance > 0 ? 'danger' : 'success'
                    )
                    ->formatStateUsing(fn (Supplier $record): string =>
                        $record->balance > 0
                            ? number_format($record->balance, 0, ',', ' ') . ' KMF'
                            : 'Aucune dette'
                    ),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Statut'),

                Tables\Filters\Filter::make('has_debt')
                    ->label('Avec dette')
                    ->query(fn ($query) => $query->where('balance', '>', 0))
                    ->toggle(),
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
            'index'  => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
        ];
    }
}