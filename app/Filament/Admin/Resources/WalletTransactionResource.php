<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\WalletTransactionResource\Pages;
use App\Models\Wallet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class WalletTransactionResource extends Resource
{
    protected static ?string $model = \App\Models\WalletTransaction::class;
    protected static ?string $modelLabel = 'Transaction';
    protected static ?string $pluralModelLabel = 'Transactions Portefeuille';
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Abonnements';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('wallet_id')
                ->label('Portefeuille')
                ->options(Wallet::with('shop')->get()->pluck('shop.name', 'id'))
                ->required()
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(fn($state, $set) => $set('shop_id', Wallet::find($state)?->shop_id)),
            Forms\Components\Hidden::make('shop_id'),
            Forms\Components\Hidden::make('user_id')->default(Auth::id()),
            Forms\Components\TextInput::make('amount')
                ->label('Montant (KMF)')
                ->numeric()
                ->required()
                ->minValue(1)
                ->suffix('KMF'),
            Forms\Components\Select::make('payment_method')
                ->label('Méthode')
                ->options(['cash' => 'Cash', 'mvola' => 'MVola', 'other' => 'Autre'])
                ->required(),
            Forms\Components\Textarea::make('notes')->label('Notes')->rows(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('wallet.shop.name')->label('Commerce'),
                Tables\Columns\TextColumn::make('reference')->label('Référence'),
                Tables\Columns\TextColumn::make('type')->label('Type')->badge(),
                Tables\Columns\TextColumn::make('payment_method')->label('Méthode')->badge(),
                Tables\Columns\TextColumn::make('amount')->label('Montant')->money('KMF'),
                Tables\Columns\TextColumn::make('balance_after')->label('Solde après')->money('KMF'),
                Tables\Columns\TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->options([
                    'recharge' => 'Recharge',
                    'subscription' => 'Abonnement',
                    'refund' => 'Remboursement',
                ]),
                Tables\Filters\SelectFilter::make('payment_method')->options([
                    'cash' => 'Cash',
                    'mvola' => 'MVola',
                    'other' => 'Autre',
                ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWalletTransactions::route('/'),
            'create' => Pages\CreateWalletTransaction::route('/create'),
            'view' => Pages\ViewWalletTransaction::route('/{record}'),
            'edit' => Pages\EditWalletTransaction::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return 'Transactions Portefeuille';
    }
}
