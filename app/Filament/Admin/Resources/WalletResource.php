<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\WalletResource\Pages;
use App\Filament\Admin\Resources\WalletResource\RelationManagers;
use App\Models\Wallet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WalletResource extends Resource
{
    protected static ?string $model = Wallet::class;
    protected static ?string $modelLabel = 'Portefeuille';
    protected static ?string $pluralModelLabel = 'Portefeuilles';
    protected static ?string $navigationIcon = 'heroicon-o-wallet';
    protected static ?string $navigationGroup = 'Abonnements';
    protected static ?int $navigationSort = 1;
    protected static ?string $routePrefix = 'wallets';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informations du portefeuille')
                ->schema([
                    Forms\Components\Select::make('shop_id')
                        ->label('Commerce')
                        ->options(\App\Models\Shop::query()->pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->preload(),
                    Forms\Components\TextInput::make('balance')
                        ->label('Solde (KMF)')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->suffix('KMF'),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Actif')
                        ->default(true),
                    Forms\Components\Textarea::make('notes')
                        ->label('Notes')
                        ->rows(2)
                        ->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('shop.name')
                    ->label('Commerce')
                    ->searchable(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Solde')
                    ->money('KMF')
                    ->color(fn($state) => $state >= 1000 ? 'success' : ($state > 0 ? 'warning' : 'danger')),
                Tables\Columns\IconColumn::make('is_active')->label('Actif')->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Statut')
                    ->trueLabel('Actifs')
                    ->falseLabel('Inactifs'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWallets::route('/'),
            'create' => Pages\CreateWallet::route('/create'),
            'view' => Pages\ViewWallet::route('/{record}'),
            'edit' => Pages\EditWallet::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return 'Portefeuilles';
    }
}
