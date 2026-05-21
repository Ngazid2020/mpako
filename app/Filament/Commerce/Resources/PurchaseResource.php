<?php

namespace App\Filament\Commerce\Resources;

use App\Filament\Commerce\Resources\PurchaseResource\Pages;
use App\Models\Product;
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

    // ═══════════════════════════════════════════════
    // FORMULAIRE
    // ═══════════════════════════════════════════════

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                // ─────────────────────────────────────
                // SECTION : Informations de l'achat
                // ─────────────────────────────────────
                Forms\Components\Section::make('Informations de l\'achat')
                    ->icon('heroicon-o-shopping-bag')
                    ->schema([

                        // Sélecteur de fournisseur
                        Forms\Components\Select::make('supplier_id')
                            ->label('Fournisseur')
                            ->options(function () {
                                $shop = Filament::getTenant();
                                if (!$shop) return [];

                                return $shop->suppliers()
                                    ->where('is_active', true)
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('Sélectionner un fournisseur...')
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nom du fournisseur')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('phone')
                                    ->label('Téléphone')
                                    ->tel(),
                            ])
                            ->createOptionUsing(function (array $data) {
                                $shop = Filament::getTenant();
                                if (!$shop) {
                                    throw new \Exception('Aucun commerce sélectionné');
                                }
                                return $shop->suppliers()->create($data)->id;
                            }),

                        // Date d'achat
                        Forms\Components\DatePicker::make('purchased_at')
                            ->label('Date d\'achat')
                            ->required()
                            ->default(today())
                            ->maxDate(today())
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        // Statut — verrouillé après validation
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options(function (?Purchase $record) {
                                // Si déjà validé, verrouiller
                                if ($record?->status === 'completed') {
                                    return ['completed' => '✅ Validé (verrouillé)'];
                                }
                                return [
                                    'pending'   => '⏳ En attente',
                                    'completed' => '✅ Valider et mettre à jour le stock',
                                ];
                            })
                            ->default('pending')
                            ->required()
                            ->native(false)
                            ->disabled(fn (?Purchase $record) => $record?->status === 'completed')
                            ->helperText(function (?Purchase $record) {
                                if ($record?->status === 'completed') {
                                    return '🔒 Cet achat est verrouillé. Pour annuler, utilisez l\'action depuis la liste.';
                                }
                                return 'Valider = le stock est mis à jour automatiquement';
                            }),

                        // Note
                        Forms\Components\Textarea::make('note')
                            ->label('Note')
                            ->placeholder('Informations supplémentaires...')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                // ─────────────────────────────────────
                // SECTION : Produits achetés
                // ─────────────────────────────────────
                Forms\Components\Section::make('Produits achetés')
                    ->icon('heroicon-o-cube')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->label('')
                            ->relationship('items')
                            ->required()
                            ->minItems(1)
                            ->validationMessages([
                                'min' => 'Vous devez ajouter au moins un produit.',
                                'required' => 'Vous devez ajouter au moins un produit.',
                            ])
                            ->schema([
                                // Sélecteur de produit avec stock affiché
                                Forms\Components\Select::make('product_id')
                                    ->label('Produit')
                                    ->options(function () {
                                        $shop = Filament::getTenant();
                                        if (!$shop) return [];

                                        return $shop->products()
                                            ->where('is_active', true)
                                            ->with('unit')
                                            ->get()
                                            ->mapWithKeys(fn ($p) => [
                                                $p->id => "{$p->name} (stock : {$p->stock_qty} {$p->unit?->abbreviation})"
                                            ]);
                                    })
                                    ->required()
                                    ->searchable()
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        if (!$state) return;

                                        $product = Product::find($state);
                                        if ($product) {
                                            $set('product_name', $product->name);
                                            $set('unit_cost', $product->buy_price);
                                        }
                                    })
                                    ->columnSpan(4),

                                // Quantité
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Quantité')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(0.01)
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                        $set('subtotal', (float) $state * (float) $get('unit_cost'));
                                    })
                                    ->columnSpan(2),

                                // Coût unitaire
                                Forms\Components\TextInput::make('unit_cost')
                                    ->label('Coût unitaire')
                                    ->numeric()
                                    ->required()
                                    ->default(0)
                                    ->minValue(0)
                                    ->suffix('KMF')
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                        $set('subtotal', (float) $state * (float) $get('quantity'));
                                    })
                                    ->columnSpan(2),

                                // Sous-total
                                Forms\Components\TextInput::make('subtotal')
                                    ->label('Sous-total')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->suffix('KMF')
                                    ->columnSpan(2),

                                // Champ caché
                                Forms\Components\Hidden::make('product_name'),
                            ])
                            ->columns(10)
                            ->addActionLabel('+ Ajouter un produit')
                            ->reorderable(false)
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::recalculateTotals($get, $set);
                            })
                            // FIX : Recalculer après suppression d'une ligne
                            ->deleteAction(
                                fn (Forms\Components\Actions\Action $action) => $action->after(
                                    fn (Get $get, Set $set) => self::recalculateTotals($get, $set)
                                )
                            ),
                    ]),

                // ─────────────────────────────────────
                // SECTION : Paiement
                // ─────────────────────────────────────
                Forms\Components\Section::make('Paiement')
                    ->icon('heroicon-o-banknotes')
                    ->schema([

                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total de l\'achat')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(true)
                            ->suffix('KMF')
                            ->helperText('Calculé automatiquement')
                            ->extraInputAttributes(['class' => 'font-bold text-lg']),

                        Forms\Components\TextInput::make('paid_amount')
                            ->label('Montant payé')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->suffix('KMF')
                            ->live(debounce: 500)
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::recalculateTotals($get, $set);
                            })
                            // FIX : Ne peut pas dépasser le total
                            ->rule(function (Get $get) {
                                return function (string $attribute, $value, $fail) use ($get) {
                                    $total = (float) $get('total_amount');
                                    if ((float) $value > $total) {
                                        $fail("Le montant payé ne peut pas dépasser le total ({$total} KMF).");
                                    }
                                };
                            })
                            // Bouton "Tout payer"
                            ->hintAction(
                                Forms\Components\Actions\Action::make('payAll')
                                    ->label('Tout payer')
                                    ->icon('heroicon-m-banknotes')
                                    ->action(function (Get $get, Set $set) {
                                        $set('paid_amount', $get('total_amount'));
                                        self::recalculateTotals($get, $set);
                                    })
                            ),

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

    // ═══════════════════════════════════════════════
    // HELPER : Recalculer les totaux
    // ═══════════════════════════════════════════════

    public static function recalculateTotals(Get $get, Set $set): void
    {
        $items = $get('items') ?? [];

        $total = collect($items)->sum(fn ($item) => (float) ($item['subtotal'] ?? 0));

        $paid = (float) ($get('paid_amount') ?? 0);
        $debt = max(0, $total - $paid);

        $set('total_amount', $total);
        $set('debt_amount', $debt);
    }

    // ═══════════════════════════════════════════════
    // TABLEAU
    // ═══════════════════════════════════════════════

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

                // Compteur de produits
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Produits')
                    ->counts('items')
                    ->badge()
                    ->color('info')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('KMF')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('paid_amount')
                    ->label('Payé')
                    ->money('KMF')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('debt_amount')
                    ->label('Reste dû')
                    ->money('KMF')
                    ->badge()
                    ->color(fn (Purchase $record): string =>
                        $record->debt_amount > 0 ? 'danger' : 'success'
                    ),

                Tables\Columns\BadgeColumn::make('payment_status')
                    ->label('Paiement')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'unpaid'  => '🔴 Non payé',
                        'partial' => '🟠 Partiel',
                        'paid'    => '✅ Payé',
                        default   => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'unpaid'  => 'danger',
                        'partial' => 'warning',
                        'paid'    => 'success',
                        default   => 'gray',
                    }),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending'   => '⏳ En attente',
                        'completed' => '✅ Validé',
                        'cancelled' => '❌ Annulé',
                        default     => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending'   => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default     => 'gray',
                    }),

                // Compteur de paiements (caché par défaut)
                Tables\Columns\TextColumn::make('supplier_payments_count')
                    ->label('Paiements')
                    ->counts('supplierPayments')
                    ->badge()
                    ->color('success')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            // Tri intelligent : pending d'abord, puis date desc
            ->defaultSort(function ($query) {
                return $query
                    ->orderByRaw("FIELD(status, 'pending', 'completed', 'cancelled')")
                    ->orderBy('purchased_at', 'desc');
            })
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut stock')
                    ->options([
                        'pending'   => '⏳ En attente',
                        'completed' => '✅ Validés',
                        'cancelled' => '❌ Annulés',
                    ]),

                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Statut paiement')
                    ->options([
                        'unpaid'  => '🔴 Non payé',
                        'partial' => '🟠 Partiel',
                        'paid'    => '✅ Payé',
                    ]),

                Tables\Filters\SelectFilter::make('supplier')
                    ->label('Fournisseur')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('today')
                    ->label("Aujourd'hui")
                    ->query(fn ($query) => $query->whereDate('purchased_at', today()))
                    ->toggle(),

                Tables\Filters\Filter::make('has_debt')
                    ->label('Avec dette')
                    ->query(fn ($query) => $query->where('debt_amount', '>', 0))
                    ->toggle(),
            ])
            ->actions([

                // ─── Action : Valider (mettre à jour le stock) ───
                Tables\Actions\Action::make('complete')
                    ->label('Valider')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Valider cet achat ?')
                    ->modalDescription('Le stock de tous les produits sera mis à jour automatiquement. Cette action est irréversible.')
                    ->modalSubmitActionLabel('Oui, valider')
                    ->modalIcon('heroicon-o-check-circle')
                    ->modalIconColor('success')
                    ->visible(fn (Purchase $record): bool => $record->status === 'pending')
                    ->action(function (Purchase $record): void {
                        $record->update(['status' => 'completed']);

                        Notification::make()
                            ->title('✅ Achat validé')
                            ->body("Le stock a été mis à jour.")
                            ->success()
                            ->send();
                    }),

                // ─── Action : Annuler ───
                Tables\Actions\Action::make('cancel')
                    ->label('Annuler')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Annuler cet achat ?')
                    ->modalDescription(function (Purchase $record) {
                        $msg = 'Cette action retire l\'achat de votre historique actif.';
                        if ($record->status === 'completed') {
                            $msg .= ' Le stock sera diminué en conséquence.';
                        }
                        if ($record->debt_amount > 0) {
                            $msg .= ' La dette fournisseur sera également annulée.';
                        }
                        return $msg;
                    })
                    ->visible(fn (Purchase $record): bool =>
                        in_array($record->status, ['pending', 'completed'])
                    )
                    ->action(function (Purchase $record): void {
                        // FIX : Libérer la dette fournisseur lors de l'annulation
                        if ($record->supplier_id && $record->debt_amount > 0) {
                            $record->supplier->decrement('balance', $record->debt_amount);
                        }

                        $record->update(['status' => 'cancelled']);

                        Notification::make()
                            ->title('Achat annulé')
                            ->success()
                            ->send();
                    }),

                // ─── Action : Payer la dette fournisseur ───
                Tables\Actions\Action::make('pay_debt')
                    ->label('Payer')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (Purchase $record): bool =>
                        $record->status === 'completed' && $record->debt_amount > 0
                    )
                    ->modalHeading(fn (Purchase $record) =>
                        "Payer la dette — {$record->reference}"
                    )
                    ->modalDescription(fn (Purchase $record) =>
                        "Fournisseur : {$record->supplier?->name} | Reste : " .
                        number_format($record->debt_amount, 0, ',', ' ') . ' KMF'
                    )
                    ->modalIcon('heroicon-o-banknotes')
                    ->modalIconColor('success')
                    ->form(function (Purchase $record): array {
                        return [
                            Forms\Components\Placeholder::make('info')
                                ->label('Achat')
                                ->content($record->reference),

                            Forms\Components\Placeholder::make('remaining')
                                ->label('Reste à payer')
                                ->content(
                                    number_format($record->debt_amount, 0, ',', ' ') . ' KMF'
                                ),

                            Forms\Components\TextInput::make('amount')
                                ->label('Montant payé')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->maxValue(fn () => $record->debt_amount)
                                ->suffix('KMF')
                                ->helperText(
                                    'Maximum : ' .
                                    number_format($record->debt_amount, 0, ',', ' ') . ' KMF'
                                )
                                ->hintAction(
                                    Forms\Components\Actions\Action::make('payAll')
                                        ->label('Tout payer')
                                        ->icon('heroicon-m-banknotes')
                                        ->action(fn (Set $set) =>
                                            $set('amount', $record->debt_amount)
                                        )
                                ),

                            Forms\Components\DatePicker::make('paid_at')
                                ->label('Date du paiement')
                                ->required()
                                ->default(today())
                                ->maxDate(today())
                                ->native(false)
                                ->displayFormat('d/m/Y'),

                            Forms\Components\TextInput::make('note')
                                ->label('Note')
                                ->placeholder('Optionnel...'),
                        ];
                    })
                    ->action(function (Purchase $record, array $data): void {
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
                                number_format($amount, 0, ',', ' ') . ' KMF payés à ' .
                                ($record->supplier?->name ?? 'fournisseur')
                            )
                            ->success()
                            ->send();
                    }),

                Tables\Actions\ViewAction::make(),

                Tables\Actions\EditAction::make()
                    ->visible(fn (Purchase $record): bool => $record->status === 'pending'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->can('delete_any_purchase') ?? false),
                ]),
            ])
            ->emptyStateHeading('Aucun achat enregistré')
            ->emptyStateDescription('Commencez par enregistrer votre premier achat fournisseur.')
            ->emptyStateIcon('heroicon-o-shopping-bag')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Nouvel achat')
                    ->icon('heroicon-o-plus'),
            ]);
    }

    // ═══════════════════════════════════════════════
    // INFOLIST (Vue détaillée)
    // ═══════════════════════════════════════════════

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([

                // ─── Section : Détails de l'achat ───
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

                        Infolists\Components\TextEntry::make('status')
                            ->label('Stock')
                            ->badge()
                            ->formatStateUsing(fn ($state) => match ($state) {
                                'pending'   => '⏳ En attente',
                                'completed' => '✅ Validé',
                                'cancelled' => '❌ Annulé',
                                default     => $state,
                            })
                            ->color(fn ($state) => match ($state) {
                                'pending'   => 'warning',
                                'completed' => 'success',
                                'cancelled' => 'danger',
                                default     => 'gray',
                            }),

                        Infolists\Components\TextEntry::make('payment_status')
                            ->label('Paiement')
                            ->badge()
                            ->formatStateUsing(fn ($state) => match ($state) {
                                'unpaid'  => '🔴 Non payé',
                                'partial' => '🟠 Partiel',
                                'paid'    => '✅ Payé',
                                default   => $state,
                            })
                            ->color(fn ($state) => match ($state) {
                                'unpaid'  => 'danger',
                                'partial' => 'warning',
                                'paid'    => 'success',
                                default   => 'gray',
                            }),

                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Enregistré par'),

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
                            ->color(fn (Purchase $record) =>
                                $record->debt_amount > 0 ? 'danger' : 'success'
                            ),

                        Infolists\Components\TextEntry::make('note')
                            ->label('Note')
                            ->columnSpanFull()
                            ->placeholder('—'),
                    ])
                    ->columns(3),

                // ─── Section : Produits achetés ───
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

                // ─── Section : Historique des paiements ───
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
                    ])
                    ->visible(fn (Purchase $record) => $record->supplierPayments()->exists()),
            ]);
    }

    // ═══════════════════════════════════════════════
    // PAGES
    // ═══════════════════════════════════════════════

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