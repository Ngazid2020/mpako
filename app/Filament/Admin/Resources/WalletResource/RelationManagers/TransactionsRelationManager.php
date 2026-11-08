<?php

namespace App\Filament\Admin\Resources\WalletResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('reference')->label('Référence')->required(),
            Forms\Components\Select::make('type')->label('Type')->options([
                'recharge' => 'Recharge', 'subscription' => 'Abonnement', 'refund' => 'Remboursement',
            ])->required(),
            Forms\Components\Select::make('payment_method')->label('Méthode')->options([
                'cash' => 'Cash', 'mvola' => 'MVola', 'other' => 'Autre', 'wallet' => 'Portefeuille',
            ])->required(),
            Forms\Components\TextInput::make('amount')->label('Montant')->numeric()->suffix('KMF'),
            Forms\Components\TextInput::make('balance_before')->label('Solde avant')->numeric()->suffix('KMF'),
            Forms\Components\TextInput::make('balance_after')->label('Solde après')->numeric()->suffix('KMF'),
            Forms\Components\Toggle::make('is_confirmed')->label('Confirmé')->default(true),
            Forms\Components\Textarea::make('notes')->label('Notes')->rows(2),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')->label('Référence'),
                Tables\Columns\TextColumn::make('type')->label('Type')->badge(),
                Tables\Columns\TextColumn::make('payment_method')->label('Méthode')->badge(),
                Tables\Columns\TextColumn::make('amount')->label('Montant')->money('KMF'),
                Tables\Columns\TextColumn::make('balance_after')->label('Solde après')->money('KMF'),
                Tables\Columns\IconColumn::make('is_confirmed')->label('Confirmé')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->label('Date')->date('d/m/Y'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->options([
                    'recharge' => 'Recharge', 'subscription' => 'Abonnement', 'refund' => 'Remboursement',
                ]),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([Tables\Actions\ViewAction::make(), Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])])
            ->defaultSort('created_at', 'desc');
    }
}
