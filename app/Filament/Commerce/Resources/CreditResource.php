<?php

namespace App\Filament\Commerce\Resources;

use App\Filament\Commerce\Resources\CreditResource\Pages;
use App\Models\Credit;
use App\Models\CreditPayment;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CreditResource extends Resource
{
    protected static ?string $model = Credit::class;

    protected static ?string $navigationIcon   = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup  = 'Crédits';
    protected static ?string $modelLabel       = 'Crédit';
    protected static ?string $pluralModelLabel = 'Crédits';
    protected static ?int    $navigationSort   = 1;

    public static function form(Form $form): Form
    {
        $shop = Filament::getTenant();

        return $form
            ->schema([
                Forms\Components\Section::make('Informations du crédit')
                    ->icon('heroicon-o-credit-card')
                    ->schema([

                        // Client
                        Forms\Components\Select::make('customer_id')
                            ->label('Client')
                            ->options(
                                fn () => $shop->customers()
                                    ->where('is_active', true)
                                    ->pluck('name', 'id')
                            )
                            ->required()
                            ->searchable()
                            ->native(false)
                            ->preload()
                            // Créer un client directement depuis le select
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nom du client')
                                    ->required(),
                                Forms\Components\TextInput::make('phone')
                                    ->label('Téléphone')
                                    ->tel(),
                                Forms\Components\TextInput::make('address')
                                    ->label('Quartier'),
                            ])
                            ->createOptionUsing(function (array $data) use ($shop) {
                                return $shop->customers()->create($data)->id;
                            }),

                        // Montant du crédit
                        Forms\Components\TextInput::make('total_amount')
                            ->label('Montant du crédit (KMF)')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->suffix('KMF')
                            ->live(debounce: 500)
                            ->afterStateUpdated(function ($state, callable $set) {
                                // remaining = total au départ
                                $set('remaining_amount', $state);
                            }),

                        // Date d'échéance
                        Forms\Components\DatePicker::make('due_date')
                            ->label('Date d\'échéance')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->minDate(today())
                            ->placeholder('Optionnel'),

                        // Description
                        Forms\Components\Textarea::make('description')
                            ->label('Description (ce qui a été pris)')
                            ->placeholder('Ex: 2kg de riz, 1 bouteille d\'huile...')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('note')
                            ->label('Note')
                            ->placeholder('Informations supplémentaires...')
                            ->columnSpanFull(),

                        // Champ caché
                        Forms\Components\Hidden::make('remaining_amount'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->weight('bold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Credit $record) => $record->customer?->phone),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Montant')
                    ->money('KMF')
                    ->sortable(),

                Tables\Columns\TextColumn::make('paid_amount')
                    ->label('Remboursé')
                    ->money('KMF')
                    ->color('success'),

                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('Reste dû')
                    ->money('KMF')
                    ->sortable()
                    ->weight('bold')
                    ->color(fn (Credit $record): string =>
                        $record->remaining_amount > 0 ? 'danger' : 'success'
                    ),

                // Badge statut
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'pending' => '🔴 Non remboursé',
                        'partial' => '🟠 Partiel',
                        'paid'    => '✅ Soldé',
                        default   => $state,
                    })
                    ->color(fn (string $state): string => match($state) {
                        'pending' => 'danger',
                        'partial' => 'warning',
                        'paid'    => 'success',
                        default   => 'gray',
                    }),

                // Alerte si en retard
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Échéance')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->color(fn (Credit $record): string =>
                        $record->isOverdue() ? 'danger' : 'gray'
                    )
                    ->description(fn (Credit $record): ?string =>
                        $record->isOverdue() ? '⚠️ En retard' : null
                    ),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending' => '🔴 Non remboursé',
                        'partial' => '🟠 Partiel',
                        'paid'    => '✅ Soldé',
                    ]),

                Tables\Filters\SelectFilter::make('customer')
                    ->label('Client')
                    ->relationship('customer', 'name'),

                Tables\Filters\Filter::make('overdue')
                    ->label('En retard')
                    ->query(fn ($query) => $query
                        ->whereNotNull('due_date')
                        ->where('due_date', '<', today())
                        ->where('status', '!=', 'paid')
                    )
                    ->toggle(),

                Tables\Filters\Filter::make('not_paid')
                    ->label('Non soldés')
                    ->query(fn ($query) => $query->where('status', '!=', 'paid'))
                    ->toggle(),
            ])
            ->actions([

                // ── Action principale : Enregistrer un remboursement ──
                Tables\Actions\Action::make('pay')
                    ->label('Remboursement')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (Credit $record): bool => $record->status !== 'paid')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Montant remboursé (KMF)')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->suffix('KMF'),

                        Forms\Components\DatePicker::make('paid_at')
                            ->label('Date du remboursement')
                            ->required()
                            ->default(today())
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\TextInput::make('note')
                            ->label('Note')
                            ->placeholder('Optionnel...'),
                    ])
                    ->action(function (Credit $record, array $data): void {
                        // Vérifier que le montant ne dépasse pas le reste dû
                        $amount = min(
                            (float) $data['amount'],
                            (float) $record->remaining_amount
                        );

                        CreditPayment::create([
                            'credit_id' => $record->id,
                            'user_id'   => auth()->id(),
                            'amount'    => $amount,
                            'paid_at'   => $data['paid_at'],
                            'note'      => $data['note'] ?? null,
                        ]);

                        Notification::make()
                            ->title('✅ Remboursement enregistré')
                            ->body(
                                number_format($amount, 0, ',', ' ') . ' KMF encaissés pour '
                                . $record->customer->name
                            )
                            ->success()
                            ->send();
                    }),

                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Détail du crédit')
                    ->schema([
                        Infolists\Components\TextEntry::make('reference')
                            ->label('Référence')
                            ->weight('bold')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('customer.name')
                            ->label('Client')
                            ->weight('bold'),

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
                            ->placeholder('Pas d\'échéance'),

                        Infolists\Components\TextEntry::make('total_amount')
                            ->label('Montant total')
                            ->money('KMF')
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('paid_amount')
                            ->label('Remboursé')
                            ->money('KMF')
                            ->color('success'),

                        Infolists\Components\TextEntry::make('remaining_amount')
                            ->label('Reste dû')
                            ->money('KMF')
                            ->weight('bold')
                            ->color(fn (Credit $record) =>
                                $record->remaining_amount > 0 ? 'danger' : 'success'
                            ),

                        Infolists\Components\TextEntry::make('description')
                            ->label('Description')
                            ->columnSpanFull()
                            ->placeholder('—'),

                        Infolists\Components\TextEntry::make('note')
                            ->label('Note')
                            ->columnSpanFull()
                            ->placeholder('—'),
                    ])
                    ->columns(3),

                // Historique des remboursements
                Infolists\Components\Section::make('Historique des remboursements')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('payments')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('paid_at')
                                    ->label('Date')
                                    ->date('d/m/Y'),

                                Infolists\Components\TextEntry::make('amount')
                                    ->label('Montant')
                                    ->money('KMF')
                                    ->weight('bold')
                                    ->color('success'),

                                Infolists\Components\TextEntry::make('user.name')
                                    ->label('Enregistré par'),

                                Infolists\Components\TextEntry::make('note')
                                    ->label('Note')
                                    ->placeholder('—'),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCredits::route('/'),
            'create' => Pages\CreateCredit::route('/create'),
            'view'   => Pages\ViewCredit::route('/{record}'),
        ];
    }
}