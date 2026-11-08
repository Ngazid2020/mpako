<?php

namespace App\Filament\Commerce\Resources;

use App\Enums\WalletStatus;
use App\Filament\Commerce\Resources\WalletResource\Pages;
use App\Filament\Commerce\Resources\WalletResource\RelationManagers;
use App\Models\Wallet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WalletResource extends Resource
{
    protected static ?string $model = Wallet::class;
    protected static ?string $modelLabel = 'Portefeuille';
    protected static ?string $pluralModelLabel = 'Portefeuille';
    protected static ?string $navigationIcon = 'heroicon-o-wallet';
    protected static ?string $navigationGroup = 'Abonnements';
    protected static ?int $navigationSort = 1;
    protected static ?string $routePrefix = 'wallets';

    public static function getNavigationLabel(): string
    {
        return 'Mon Portefeuille';
    }

    public static function getModelLabel(): string
    {
        return 'Portefeuille';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informations du portefeuille')
                ->schema([
                    Forms\Components\TextInput::make('balance')
                        ->label('Solde (KMF)')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->suffix('KMF')
                        ->disabled(),
                    Forms\Components\Select::make('status')
                        ->label('Statut')
                        ->options(WalletStatus::options())
                        ->default('draft')
                        ->disabled(),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Actif')
                        ->disabled(),
                    Forms\Components\Textarea::make('notes')
                        ->label('Notes')
                        ->rows(2)
                        ->disabled()
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
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),
                Tables\Columns\IconColumn::make('is_active')->label('Actif')->boolean(),
                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Soumis le')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('validated_at')
                    ->label('Validé le')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options(WalletStatus::options()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
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
            'view' => Pages\ViewWallet::route('/{record}'),
        ];
    }

    // Le solde ne se modifie QUE via des transactions (recharge validée par l'admin)
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('shop.members', function($query) {
                $query->where('users.id', auth()?->id());
            });
    }

    public static function canCreate(): bool
    {
        return false; // Le portefeuille est créé automatiquement pour chaque commerce
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }
}
