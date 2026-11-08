<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SubscriptionResource\Pages;
use App\Filament\Admin\Resources\SubscriptionResource\RelationManagers;
use App\Models\Subscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;
    protected static ?string $modelLabel = 'Abonnement';
    protected static ?string $pluralModelLabel = 'Abonnements';
    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $navigationGroup = 'Abonnements';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('shop_id')
                ->label('Commerce')
                ->options(\App\Models\Shop::query()->pluck('name', 'id'))
                ->required()
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(fn($state, $set) => $set('wallet_id', \App\Models\Wallet::where('shop_id', $state)->first()?->id)),
            Forms\Components\Select::make('wallet_id')
                ->label('Portefeuille')
                ->options(\App\Models\Wallet::query()->pluck('balance', 'id'))
                ->required()
                ->searchable()
                ->preload(),
            Forms\Components\Select::make('subscription_plan_id')
                ->label('Forfait')
                ->options(\App\Models\SubscriptionPlan::query()->pluck('name', 'id'))
                ->required()
                ->searchable()
                ->preload(),
            Forms\Components\DateTimePicker::make('starts_at')
                ->label('Date de début')
                ->required()
                ->default(now()),
            Forms\Components\DateTimePicker::make('ends_at')
                ->label('Date de fin')
                ->required()
                ->default(now()->addDays(30)),
            Forms\Components\Toggle::make('is_active')->label('Actif')->default(true),
            Forms\Components\Toggle::make('is_auto_renew')->label('Renouvellement auto')->default(false),
            Forms\Components\Textarea::make('notes')->label('Notes')->rows(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('shop.name')->label('Commerce')->searchable(),
                Tables\Columns\TextColumn::make('plan.name')->label('Forfait'),
                Tables\Columns\TextColumn::make('wallet.balance')->label('Solde')->money('KMF'),
                Tables\Columns\TextColumn::make('starts_at')->label('Début')->date('d/m/Y'),
                Tables\Columns\TextColumn::make('ends_at')->label('Fin')->date('d/m/Y'),
                Tables\Columns\TextColumn::make('status_label')->label('Statut')->badge(),
                Tables\Columns\TextColumn::make('days_remaining')->label('Jours restants')->suffix(' j'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return 'Abonnements';
    }
}
