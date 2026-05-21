<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\EmployeeResource\Pages;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

class EmployeeResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon   = 'heroicon-o-user-group';
    protected static ?string $navigationGroup  = 'Équipe';
    protected static ?string $modelLabel       = 'Employé';
    protected static ?string $pluralModelLabel = 'Employés';
    protected static ?int    $navigationSort   = 1;

    // ─────────────────────────────────────────────
    // PERMISSIONS — Seul le owner peut voir
    // ─────────────────────────────────────────────

    // public static function canViewAny(): bool
    // {
    //     return auth()->user()?->hasRole('owner') ?? false;
    // }

    // public static function canCreate(): bool
    // {
    //     return auth()->user()?->hasRole('owner') ?? false;
    // }

    // public static function canEdit($record): bool
    // {
    //     if (!auth()->user()?->hasRole('owner')) {
    //         return false;
    //     }

    //     // On ne peut pas modifier un autre owner
    //     return !$record->hasRole('owner') || $record->id === auth()->id();
    // }

    // public static function canDelete($record): bool
    // {
    //     return auth()->user()?->hasRole('owner') && !$record->hasRole('owner');
    // }

    // ─────────────────────────────────────────────
    // QUERY — Limiter aux membres du shop courant
    // ─────────────────────────────────────────────

    // public static function getEloquentQuery(): Builder
    // {
    //     $shop = Filament::getTenant();

    //     return parent::getEloquentQuery()
    //         ->whereHas('shops', function (Builder $query) use ($shop) {
    //             $query->where('shops.id', $shop->id);
    //         });
    // }

    // ─────────────────────────────────────────────
    // FORMULAIRE
    // ─────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Section::make('Informations de l\'employé')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom complet')
                            ->placeholder('Ex: Fatima Said')
                            ->required()
                            ->maxLength(255)
                            ->autofocus(),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->placeholder('fatima@example.com')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('password')
                            ->label('Mot de passe')
                            ->password()
                            ->revealable()
                            ->required(fn(string $operation): bool => $operation === 'create')
                            ->dehydrated(fn(?string $state): bool => filled($state))
                            ->maxLength(255)
                            ->helperText(
                                fn(string $operation) => $operation === 'edit'
                                    ? 'Laisser vide pour ne pas changer'
                                    : 'L\'employé pourra changer son mot de passe ensuite'
                            ),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Rôle dans le commerce')
                    ->icon('heroicon-o-key')
                    ->schema([
                        Forms\Components\Select::make('roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->label('Quel rôle ?')
                            ->preload()
                            ->required(),
                    ]),
            ]);
    }

    // ─────────────────────────────────────────────
    // TABLEAU
    // ─────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn(User $record) => $record->email),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Rôle')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => match ($state) {
                        'owner'   => '👑 Propriétaire',
                        'manager' => '💼 Gérant',
                        'cashier' => '🛒 Caissier',
                        default   => $state,
                    })
                    ->color(fn(string $state) => match ($state) {
                        'owner'   => 'danger',
                        'manager' => 'warning',
                        'cashier' => 'info',
                        default   => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Inscrit le')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->label('Rôle')
                    ->relationship('roles', 'name')
                    ->options([
                        'manager' => '💼 Gérant',
                        'cashier' => '🛒 Caissier',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->slideOver()
                    ->visible(fn(User $record) => !$record->hasRole('owner')),

                Tables\Actions\Action::make('remove')
                    ->label('🗑️ Retirer')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Retirer cet employé ?')
                    ->modalDescription('Il ne pourra plus accéder à ce commerce.')
                    ->visible(fn(User $record) => !$record->hasRole('owner'))
                    ->action(function (User $record) {
                        $shop = Filament::getTenant();
                        $shop->members()->detach($record->id);

                        Notification::make()
                            ->title('Employé retiré')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
        ];
    }
}
