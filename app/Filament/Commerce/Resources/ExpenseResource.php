<?php

namespace App\Filament\Commerce\Resources;

use App\Filament\Commerce\Resources\ExpenseResource\Pages;
use App\Models\Expense;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon   = 'heroicon-o-arrow-trending-down';
    protected static ?string $navigationGroup  = 'Dépenses';
    protected static ?string $modelLabel       = 'Dépense';
    protected static ?string $pluralModelLabel = 'Dépenses';
    protected static ?int    $navigationSort   = 1;

    public static function form(Form $form): Form
    {
        $shop = Filament::getTenant();

        return $form
            ->schema([
                Forms\Components\Section::make('Informations de la dépense')
                    ->icon('heroicon-o-arrow-trending-down')
                    ->schema([

                        // Montant — en premier car c'est le plus important
                        Forms\Components\TextInput::make('amount')
                            ->label('Montant (KMF)')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->suffix('KMF')
                            ->autofocus(),

                        // Description
                        Forms\Components\TextInput::make('description')
                            ->label('Description')
                            ->placeholder('Ex: Loyer du mois de mai, Taxi marchandises...')
                            ->required()
                            ->maxLength(255),

                        // Catégorie
                        Forms\Components\Select::make('expense_category_id')
                            ->label('Catégorie')
                            ->options(
                                fn () => $shop->expenseCategories()
                                    ->where('is_active', true)
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->native(false)
                            ->placeholder('Sélectionner...')
                            // Créer une catégorie à la volée
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nom')
                                    ->required(),
                                Forms\Components\ColorPicker::make('color')
                                    ->label('Couleur')
                                    ->default('#6366f1'),
                            ])
                            ->createOptionUsing(function (array $data) use ($shop) {
                                return $shop->expenseCategories()->create($data)->id;
                            }),

                        // Date de la dépense
                        Forms\Components\DatePicker::make('spent_at')
                            ->label('Date')
                            ->required()
                            ->default(today())
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        // Note
                        Forms\Components\TextInput::make('note')
                            ->label('Note')
                            ->placeholder('Optionnel...')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('spent_at')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (Expense $record) => $record->category?->name),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Catégorie')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant')
                    ->money('KMF')
                    ->sortable()
                    ->weight('bold')
                    ->color('danger'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Par')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('spent_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('expense_category_id')
                    ->label('Catégorie')
                    ->relationship('category', 'name'),

                Tables\Filters\Filter::make('today')
                    ->label("Aujourd'hui")
                    ->query(fn ($query) => $query->whereDate('spent_at', today()))
                    ->toggle(),

                Tables\Filters\Filter::make('this_month')
                    ->label('Ce mois')
                    ->query(fn ($query) => $query
                        ->whereMonth('spent_at', now()->month)
                        ->whereYear('spent_at', now()->year)
                    )
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->slideOver(),
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
            'index'  => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
        ];
    }
}