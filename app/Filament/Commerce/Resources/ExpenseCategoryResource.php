<?php

namespace App\Filament\Commerce\Resources;

use App\Filament\Commerce\Resources\ExpenseCategoryResource\Pages;
use App\Models\ExpenseCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ExpenseCategoryResource extends Resource
{
    protected static ?string $model = ExpenseCategory::class;

    protected static ?string $navigationIcon   = 'heroicon-o-tag';
    protected static ?string $navigationGroup  = 'Dépenses';
    protected static ?string $modelLabel       = 'Catégorie de dépense';
    protected static ?string $pluralModelLabel = 'Catégories de dépenses';
    protected static ?int    $navigationSort   = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom de la catégorie')
                            ->placeholder('Ex: Loyer, Transport, Électricité...')
                            ->required()
                            ->maxLength(255)
                            ->autofocus(),

                        Forms\Components\ColorPicker::make('color')
                            ->label('Couleur')
                            ->default('#6366f1'),

                        Forms\Components\Select::make('icon')
                            ->label('Icône')
                            ->options([
                                'heroicon-o-home'           => '🏠 Loyer / Local',
                                'heroicon-o-bolt'           => '⚡ Électricité',
                                'heroicon-o-truck'          => '🚗 Transport',
                                'heroicon-o-user'           => '👤 Salaire',
                                'heroicon-o-cube'           => '📦 Emballages',
                                'heroicon-o-wrench'         => '🔧 Réparation',
                                'heroicon-o-device-phone-mobile' => '📱 Téléphone',
                                'heroicon-o-shopping-bag'   => '🛍️ Divers achats',
                                'heroicon-o-banknotes'      => '💵 Autres',
                            ])
                            ->default('heroicon-o-banknotes')
                            ->native(false),

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

                Tables\Columns\TextColumn::make('expenses_count')
                    ->label('Dépenses')
                    ->counts('expenses')
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
            'index'  => Pages\ListExpenseCategories::route('/'),
            'create' => Pages\CreateExpenseCategory::route('/create'),
        ];
    }
}