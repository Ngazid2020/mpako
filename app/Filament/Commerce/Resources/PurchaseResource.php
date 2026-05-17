<?php

namespace App\Filament\Commerce\Resources;

use App\Filament\Commerce\Resources\PurchaseResource\Pages;
use App\Models\Purchase;
use App\Models\SupplierPayment;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PurchaseResource extends Resource
{
    protected static ?string $model = Purchase::class;

    protected static ?string $navigationIcon   = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup  = 'Achats';
    protected static ?string $modelLabel       = 'Achat';
    protected static ?string $pluralModelLabel = 'Achats';
    protected static ?int    $navigationSort   = 1;

    public static function form(Form $form): Form
    {
        $shop = Filament::getTenant();

        return $form
            ->schema([

                // ── Section principale ──
                Forms\Components\Section::make('Informations de l\'achat')
                    ->icon('heroicon-o-shopping-bag')
                    ->schema([

                        Forms\Components\Select::make('supplier_id')
                            ->label('Fournisseur')
                            ->options(
                                fn() => $shop->suppliers()
                                    ->where('is_active', true)
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->native(false)
                            ->placeholder('Sélectionner un fournisseur...')
                            // Créer un fournisseur directement depuis le select
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nom du fournisseur')
                                    ->required(),
                                Forms\Components\TextInput::make('phone')
                                    ->label('Téléphone')
                                    ->tel(),
                            ])
                            ->createOptionUsing(function (array $data) use ($shop) {
                                return $shop->suppliers()->create($data)->id;
                            }),

                        Forms\Components\DatePicker::make('purchased_at')
                            ->label('Date d\'achat')
                            ->required()
                            ->default(today())
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'pending'   => '⏳ En attente',
                                'completed' => '✅ Valider et mettre à jour le stock',
                            ])
                            ->default('pending')
                            ->required()
                            ->native(false)
                            ->helperText('Valider = le stock est mis à jour automatiquement'),

                        Forms\Components\Textarea::make('note')
                            ->label('Note')
                            ->placeholder('Informations supplémentaires...')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                // ── Section produits achetés ──
                Forms\Components\Section::make('Produits achetés')
                    ->icon('heroicon-o-cube')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->label('')
                            ->relationship('items')
                            ->schema([
                                // Sélecteur de produit
                                Forms\Components\Select::make('product_id')
                                    ->label('Produit')
                                    ->options(
                                        fn() => $shop->products()
                                            ->where('is_active', true)
                                            ->pluck('name', 'id')
                                    )
                                    ->required()
                                    ->searchable()
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(function (
                                        $state,
                                        Set $set
                                    ) {
                                        if (!$state) return;

                                        $product = \App\Models\Product::find($state);

                                        if ($product) {
                                            // Pré-remplir le nom et le coût unitaire
                                            $set('product_name', $product->name);
                                            $set('unit_cost', $product->buy_price);
                                        }
                                    })
                                    ->columnSpan(4),

                                // Quantité achetée
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Quantité')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(0.01)
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(function (
                                        $state,
                                        Get $get,
                                        Set $set
                                    ) {
                                        // Recalculer le sous-total
                                        $set('subtotal', (float) $state * (float) $get('unit_cost'));
                                    })
                                    ->columnSpan(2),

                                // Coût unitaire
                                Forms\Components\TextInput::make('unit_cost')
                                    ->label('Coût unitaire (KMF)')
                                    ->numeric()
                                    ->required()
                                    ->default(0)
                                    ->minValue(0)
                                    ->suffix('KMF')
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(function (
                                        $state,
                                        Get $get,
                                        Set $set
                                    ) {
                                        // Recalculer le sous-total
                                        $set('subtotal', (float) $state * (float) $get('quantity'));
                                    })
                                    ->columnSpan(2),

                                // Sous-total (calculé automatiquement)
                                Forms\Components\TextInput::make('subtotal')
                                    ->label('Sous-total (KMF)')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->suffix('KMF')
                                    ->columnSpan(2),

                                // Champ caché pour le nom du produit
                                Forms\Components\Hidden::make('product_name'),
                            ])
                            ->columns(10)
                            ->addActionLabel('+ Ajouter un produit')
                            ->reorderable(false)
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::recalculateTotals($get, $set);
                            }),
                    ]),

                // ── Section paiement ──
                Forms\Components\Section::make('Paiement')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total de l\'achat')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(true)
                            ->suffix('KMF')
                            ->helperText('Calculé automatiquement'),

                        Forms\Components\TextInput::make('paid_amount')
                            ->label('Montant payé (KMF)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->suffix('KMF')
                            ->live(debounce: 500)
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::recalculateTotals($get, $set);
                            }),

                        Forms\Components\TextInput::make('debt_amount')
                            ->label('Reste à payer (dette)')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(true)
                            ->suffix('KMF')
                            ->helperText('Mis à jour sur la balance du fournisseur'),
                    ])
                    ->columns(3),
            ]);
    }

    // ─────────────────────────────────────────────
    // HELPER : Recalculer les totaux
    // ─────────────────────────────────────────────

    public static function recalculateTotals(Get $get, Set $set): void
    {
        $items = $get('items') ?? [];

        // Total = somme des sous-totaux
        $total = collect($items)->sum(
            fn($item) =>
            (float) ($item['subtotal'] ?? 0)
        );

        $paid = (float) ($get('paid_amount') ?? 0);
        $debt = max(0, $total - $paid);

        $set('total_amount', $total);
        $set('debt_amount', $debt);
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

                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Fournisseur')
                    ->searchable()
                    ->placeholder('Sans fournisseur'),

                Tables\Columns\TextColumn::make('purchased_at')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('KMF')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('paid_amount')
                    ->label('Payé')
                    ->money('KMF'),

                Tables\Columns\TextColumn::make('debt_amount')
                    ->label('Reste dû')
                    ->money('KMF')
                    ->badge()
                    ->color(
                        fn(Purchase $record): string =>
                        $record->debt_amount > 0 ? 'danger' : 'success'
                    ),
                // Après la colonne debt_amount
                Tables\Columns\BadgeColumn::make('payment_status')
                    ->label('Paiement')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'unpaid'  => '🔴 Non payé',
                        'partial' => '🟠 Partiel',
                        'paid'    => '✅ Payé',
                        default   => $state,
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'unpaid'  => 'danger',
                        'partial' => 'warning',
                        'paid'    => 'success',
                        default   => 'gray',
                    }),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'pending'   => '⏳ En attente',
                        'completed' => '✅ Validé',
                        'cancelled' => '❌ Annulé',
                        default     => $state,
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'pending'   => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default     => 'gray',
                    }),
            ])
            ->defaultSort('purchased_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending'   => 'En attente',
                        'completed' => 'Validés',
                        'cancelled' => 'Annulés',
                    ]),

                Tables\Filters\SelectFilter::make('supplier')
                    ->label('Fournisseur')
                    ->relationship('supplier', 'name'),

                Tables\Filters\Filter::make('today')
                    ->label("Aujourd'hui")
                    ->query(fn($query) => $query->whereDate('purchased_at', today()))
                    ->toggle(),
            ])
            ->actions([
                // Action rapide pour valider un achat en attente
                Tables\Actions\Action::make('complete')
                    ->label('Valider')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Valider cet achat ?')
                    ->modalDescription('Le stock de tous les produits sera mis à jour automatiquement.')
                    ->modalSubmitActionLabel('Oui, valider')
                    ->visible(fn(Purchase $record): bool => $record->status === 'pending')
                    ->action(function (Purchase $record): void {
                        $record->update(['status' => 'completed']);
                    }),

                // Action pour annuler
                Tables\Actions\Action::make('cancel')
                    ->label('Annuler')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn(Purchase $record): bool => $record->status === 'pending')
                    ->action(function (Purchase $record): void {
                        $record->update(['status' => 'cancelled']);
                    }),
                // Action : Payer la dette fournisseur
                Tables\Actions\Action::make('pay_debt')
                    ->label('Payer')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    // Visible seulement si l'achat est complété ET qu'il reste une dette
                    ->visible(
                        fn(Purchase $record): bool =>
                        $record->status === 'completed'
                            && $record->debt_amount > 0
                    )
                    ->form(function (Purchase $record): array {
                        return [
                            // Informations contextuelles (lecture seule)
                            Forms\Components\Placeholder::make('info')
                                ->label('Achat')
                                ->content($record->reference),

                            Forms\Components\Placeholder::make('remaining')
                                ->label('Reste à payer')
                                ->content(
                                    number_format($record->debt_amount, 0, ',', ' ') . ' KMF'
                                ),

                            // Montant à payer
                            Forms\Components\TextInput::make('amount')
                                ->label('Montant payé (KMF)')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->maxValue(fn() => $record->debt_amount)
                                ->suffix('KMF')
                                ->helperText(
                                    'Maximum : '
                                        . number_format($record->debt_amount, 0, ',', ' ')
                                        . ' KMF'
                                ),

                            Forms\Components\DatePicker::make('paid_at')
                                ->label('Date du paiement')
                                ->required()
                                ->default(today())
                                ->native(false)
                                ->displayFormat('d/m/Y'),

                            Forms\Components\TextInput::make('note')
                                ->label('Note')
                                ->placeholder('Optionnel...'),
                        ];
                    })
                    ->action(function (Purchase $record, array $data): void {
                        // Sécurité : ne pas dépasser le reste dû
                        $amount = min(
                            (float) $data['amount'],
                            (float) $record->debt_amount
                        );

                        SupplierPayment::create([
                            'purchase_id' => $record->id,
                            'supplier_id' => $record->supplier_id,
                            'user_id'     => auth()->id(),
                            'amount'      => $amount,
                            'paid_at'     => $data['paid_at'],
                            'note'        => $data['note'] ?? null,
                        ]);

                        Notification::make()
                            ->title('✅ Paiement enregistré')
                            ->body(
                                number_format($amount, 0, ',', ' ')
                                    . ' KMF payés à '
                                    . ($record->supplier?->name ?? 'fournisseur')
                            )
                            ->success()
                            ->send();
                    }),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn(Purchase $record): bool => $record->status === 'pending'),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Achat')
                    ->schema([
                        Infolists\Components\TextEntry::make('reference')
                            ->label('Référence')
                            ->weight('bold')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('supplier.name')
                            ->label('Fournisseur')
                            ->placeholder('Sans fournisseur'),

                        Infolists\Components\TextEntry::make('purchased_at')
                            ->label('Date')
                            ->date('d/m/Y'),

                        // Statut stock
                        Infolists\Components\TextEntry::make('status')
                            ->label('Stock')
                            ->badge()
                            ->formatStateUsing(fn($state) => match ($state) {
                                'pending'   => '⏳ En attente',
                                'completed' => '✅ Validé',
                                'cancelled' => '❌ Annulé',
                                default     => $state,
                            })
                            ->color(fn($state) => match ($state) {
                                'pending'   => 'warning',
                                'completed' => 'success',
                                'cancelled' => 'danger',
                                default     => 'gray',
                            }),

                        // Statut paiement ← NOUVEAU
                        Infolists\Components\TextEntry::make('payment_status')
                            ->label('Paiement')
                            ->badge()
                            ->formatStateUsing(fn($state) => match ($state) {
                                'unpaid'  => '🔴 Non payé',
                                'partial' => '🟠 Partiel',
                                'paid'    => '✅ Payé',
                                default   => $state,
                            })
                            ->color(fn($state) => match ($state) {
                                'unpaid'  => 'danger',
                                'partial' => 'warning',
                                'paid'    => 'success',
                                default   => 'gray',
                            }),

                        Infolists\Components\TextEntry::make('total_amount')
                            ->label('Total')
                            ->money('KMF')
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('paid_amount')
                            ->label('Déjà payé')
                            ->money('KMF')
                            ->color('success'),

                        Infolists\Components\TextEntry::make('debt_amount')
                            ->label('Reste dû')
                            ->money('KMF')
                            ->weight('bold')
                            ->color(
                                fn(Purchase $record) =>
                                $record->debt_amount > 0 ? 'danger' : 'success'
                            ),

                        Infolists\Components\TextEntry::make('note')
                            ->label('Note')
                            ->columnSpanFull()
                            ->placeholder('—'),
                    ])
                    ->columns(3),

                // Produits achetés
                Infolists\Components\Section::make('Produits achetés')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('product_name')
                                    ->label('Produit')
                                    ->weight('bold'),

                                Infolists\Components\TextEntry::make('quantity')
                                    ->label('Quantité'),

                                Infolists\Components\TextEntry::make('unit_cost')
                                    ->label('Coût unitaire')
                                    ->money('KMF'),

                                Infolists\Components\TextEntry::make('subtotal')
                                    ->label('Sous-total')
                                    ->money('KMF')
                                    ->weight('bold'),
                            ])
                            ->columns(4),
                    ]),

                // ── Historique des paiements ← NOUVEAU ──
                Infolists\Components\Section::make('Historique des paiements fournisseur')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('supplierPayments')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('paid_at')
                                    ->label('Date')
                                    ->date('d/m/Y'),

                                Infolists\Components\TextEntry::make('amount')
                                    ->label('Montant payé')
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
            'index'  => Pages\ListPurchases::route('/'),
            'create' => Pages\CreatePurchase::route('/create'),
            'view'   => Pages\ViewPurchase::route('/{record}'),
            'edit'   => Pages\EditPurchase::route('/{record}/edit'),
        ];
    }
}
