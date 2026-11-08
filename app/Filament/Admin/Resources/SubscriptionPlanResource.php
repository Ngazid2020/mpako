<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SubscriptionPlanResource\Pages;
use App\Filament\Admin\Resources\SubscriptionPlanResource\RelationManagers;
use App\Models\SubscriptionPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SubscriptionPlanResource extends Resource
{
    protected static ?string $model = SubscriptionPlan::class;
    protected static ?string $modelLabel = 'Forfait';
    protected static ?string $pluralModelLabel = 'Forfaits';
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';
    protected static ?string $navigationGroup = 'Abonnements';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nom')
                ->required()
                ->maxLength(255),
            Forms\Components\Textarea::make('description')
                ->label('Description')
                ->rows(3),
            Forms\Components\TextInput::make('price')
                ->label('Prix (KMF)')
                ->numeric()
                ->required()
                ->suffix('KMF'),
            Forms\Components\TextInput::make('duration_days')
                ->label('Durée (jours)')
                ->numeric()
                ->required()
                ->default(30),
            Forms\Components\Toggle::make('is_active')
                ->label('Actif')
                ->default(true),
            Forms\Components\Toggle::make('is_default')
                ->label('Par défaut')
                ->default(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Prix')
                    ->money('KMF'),
                Tables\Columns\TextColumn::make('duration_days')
                    ->label('Durée')
                    ->suffix(' jours'),
                Tables\Columns\IconColumn::make('is_active')->label('Actif')->boolean(),
                Tables\Columns\IconColumn::make('is_default')->label('Défaut')->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // La FK subscriptions.subscription_plan_id est en cascade : on interdit
                // la suppression d'un forfait utilisé (sinon ses abonnements disparaissent).
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn($record) => $record->subscriptions()->exists()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(fn($records) => $records->each(
                            fn($plan) => $plan->subscriptions()->exists() ? null : $plan->delete()
                        )),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SubscriptionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptionPlans::route('/'),
            'create' => Pages\CreateSubscriptionPlan::route('/create'),
            'edit' => Pages\EditSubscriptionPlan::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return 'Forfaits d\'abonnement';
    }
}
