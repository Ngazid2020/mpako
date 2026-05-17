<?php

namespace App\Filament\Commerce\Resources;

use App\Filament\Commerce\Resources\CustomerResource\Pages;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon   = 'heroicon-o-users';
    protected static ?string $navigationGroup  = 'Crédits';
    protected static ?string $modelLabel       = 'Client';
    protected static ?string $pluralModelLabel = 'Clients';
    protected static ?int    $navigationSort   = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations du client')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom complet')
                            ->placeholder('Ex: Ahmed Ali, Mariama Said...')
                            ->required()
                            ->maxLength(255)
                            ->autofocus(),

                        Forms\Components\TextInput::make('phone')
                            ->label('Téléphone')
                            ->placeholder('Ex: 333 00 00')
                            ->tel(),

                        Forms\Components\TextInput::make('address')
                            ->label('Quartier / Localisation')
                            ->placeholder('Ex: Bandamadji, Itsandra...')
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
                    ->label('Client')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Customer $record) => $record->address),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Téléphone')
                    ->icon('heroicon-m-phone')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('credits_count')
                    ->label('Crédits')
                    ->counts('credits')
                    ->badge()
                    ->color('info'),

                // Balance totale due
                Tables\Columns\TextColumn::make('balance')
                    ->label('Total dû')
                    ->sortable()
                    ->badge()
                    ->color(fn (Customer $record): string =>
                        $record->balance > 0 ? 'danger' : 'success'
                    )
                    ->formatStateUsing(fn (Customer $record): string =>
                        $record->balance > 0
                            ? number_format($record->balance, 0, ',', ' ') . ' KMF'
                            : '✅ Aucune dette'
                    ),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
            ])
            ->defaultSort('balance', 'desc') // Les plus endettés en premier
            ->filters([
                Tables\Filters\Filter::make('has_debt')
                    ->label('Avec dette')
                    ->query(fn ($query) => $query->where('balance', '>', 0))
                    ->toggle(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Statut'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->slideOver(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Client')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label('Nom')
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('phone')
                            ->label('Téléphone')
                            ->placeholder('—'),

                        Infolists\Components\TextEntry::make('address')
                            ->label('Adresse')
                            ->placeholder('—'),

                        Infolists\Components\TextEntry::make('balance')
                            ->label('Total dû')
                            ->money('KMF')
                            ->weight('bold')
                            ->color(fn (Customer $record) =>
                                $record->balance > 0 ? 'danger' : 'success'
                            ),
                    ])
                    ->columns(2),

                // Historique des crédits du client
                Infolists\Components\Section::make('Historique des crédits')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('credits')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('reference')
                                    ->label('Référence')
                                    ->weight('bold'),

                                Infolists\Components\TextEntry::make('total_amount')
                                    ->label('Montant')
                                    ->money('KMF'),

                                Infolists\Components\TextEntry::make('remaining_amount')
                                    ->label('Reste dû')
                                    ->money('KMF')
                                    ->color(fn ($record) =>
                                        $record->remaining_amount > 0 ? 'danger' : 'success'
                                    ),

                                Infolists\Components\TextEntry::make('status')
                                    ->label('Statut')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => match($state) {
                                        'pending' => '🔴 Non remboursé',
                                        'partial' => '🟠 Partiel',
                                        'paid'    => '✅ Soldé',
                                        default   => $state,
                                    })
                                    ->color(fn ($state) => match($state) {
                                        'pending' => 'danger',
                                        'partial' => 'warning',
                                        'paid'    => 'success',
                                        default   => 'gray',
                                    }),

                                Infolists\Components\TextEntry::make('due_date')
                                    ->label('Échéance')
                                    ->date('d/m/Y')
                                    ->placeholder('—'),
                            ])
                            ->columns(5),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'view'   => Pages\ViewCustomer::route('/{record}'),
        ];
    }
}