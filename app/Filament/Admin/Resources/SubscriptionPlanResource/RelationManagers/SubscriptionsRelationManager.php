<?php

namespace App\Filament\Admin\Resources\SubscriptionPlanResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SubscriptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'subscriptions';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('shop_id')->label('Commerce')->options(\App\Models\Shop::query()->pluck('name', 'id'))->required()->searchable()->preload(),
            Forms\Components\Select::make('wallet_id')->label('Portefeuille')->options(\App\Models\Wallet::query()->pluck('balance', 'id'))->required()->searchable()->preload(),
            Forms\Components\DateTimePicker::make('starts_at')->label('Date de début')->required()->default(now()),
            Forms\Components\DateTimePicker::make('ends_at')->label('Date de fin')->required()->default(now()->addDays(30)),
            Forms\Components\Toggle::make('is_active')->label('Actif')->default(true),
            Forms\Components\Toggle::make('is_auto_renew')->label('Renouvellement auto')->default(false),
            Forms\Components\Textarea::make('notes')->label('Notes')->rows(2),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('shop.name')->label('Commerce'),
                Tables\Columns\TextColumn::make('wallet.balance')->label('Solde')->money('KMF'),
                Tables\Columns\TextColumn::make('starts_at')->label('Début')->date('d/m/Y'),
                Tables\Columns\TextColumn::make('ends_at')->label('Fin')->date('d/m/Y'),
                Tables\Columns\TextColumn::make('status_label')->label('Statut')->badge(),
                Tables\Columns\TextColumn::make('days_remaining')->label('Jours restants')->suffix(' j'),
            ])
            ->filters([Tables\Filters\TernaryFilter::make('is_active')])
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([Tables\Actions\ViewAction::make(), Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }
}
