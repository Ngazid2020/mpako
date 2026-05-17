<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ShopResource\Pages;
use App\Models\Shop;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ShopResource extends Resource
{
    protected static ?string $model = Shop::class;

    // Icône et navigation
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationGroup = 'Gestion des commerces';
    protected static ?string $modelLabel = 'Commerce';
    protected static ?string $pluralModelLabel = 'Commerces';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Section : Identité
                Forms\Components\Section::make('Identité du commerce')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255)
                            ->live(debounce: 500)
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('slug', Str::slug($state));
                            }),

                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                    ])
                    ->columns(2),

                // Section : Localisation
                Forms\Components\Section::make('Localisation')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Forms\Components\Select::make('island')
                            ->label('Île')
                            ->options([
                                'Grande Comore' => '🏝️ Grande Comore',
                                'Anjouan' => '🏝️ Anjouan',
                                'Mohéli' => '🏝️ Mohéli',
                                'Mayotte' => '🏝️ Mayotte',
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('city')
                            ->label('Ville'),

                        Forms\Components\TextInput::make('address')
                            ->label('Adresse')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                // Section : Contact
                Forms\Components\Section::make('Contact')
                    ->icon('heroicon-o-phone')
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->label('Téléphone')
                            ->tel(),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email(),
                    ])
                    ->columns(2),

                // Section : Statut
                Forms\Components\Section::make('Statut')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Commerce actif')
                            ->helperText('Désactiver un commerce bloque l\'accès de ses membres')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Commerce')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('island')
                    ->label('Île')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Grande Comore' => 'success',
                        'Anjouan' => 'info',
                        'Mohéli' => 'warning',
                        'Mayotte' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('city')
                    ->label('Ville')
                    ->searchable(),

                Tables\Columns\TextColumn::make('members_count')
                    ->label('Membres')
                    ->counts('members')
                    ->badge()
                    ->color('info'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('island')
                    ->label('Île')
                    ->options([
                        'Grande Comore' => 'Grande Comore',
                        'Anjouan' => 'Anjouan',
                        'Mohéli' => 'Mohéli',
                        'Mayotte' => 'Mayotte',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Statut')
                    ->trueLabel('Actifs')
                    ->falseLabel('Inactifs'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShops::route('/'),
            'create' => Pages\CreateShop::route('/create'),
            'edit' => Pages\EditShop::route('/{record}/edit'),
        ];
    }
}