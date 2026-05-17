<?php

namespace App\Filament\Commerce\Pages;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\RegisterTenant;
use Illuminate\Support\Str;

class RegisterShop extends RegisterTenant
{
    // Label de la page
    public static function getLabel(): string
    {
        return 'Créer mon commerce';
    }

    // Formulaire de création du commerce
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Nom du commerce')
                    ->placeholder('Ex: Boutique Ali, Épicerie du coin...')
                    ->required()
                    ->maxLength(255)
                    ->live(debounce: 500)
                    ->afterStateUpdated(function ($state, callable $set) {
                        $set('slug', Str::slug($state));
                    }),

                TextInput::make('slug')
                    ->label('Identifiant unique')
                    ->required()
                    ->unique('shops', 'slug')
                    ->maxLength(255)
                    ->helperText('Généré automatiquement à partir du nom'),

                Select::make('island')
                    ->label('Île')
                    ->options([
                        'Grande Comore' => 'Grande Comore (Ngazidja)',
                        'Anjouan' => 'Anjouan (Ndzuani)',
                        'Mohéli' => 'Mohéli (Mwali)',
                        // 'Mayotte' => 'Mayotte (Maore)',
                    ])
                    ->required()
                    ->native(false), // Menu déroulant stylé

                TextInput::make('city')
                    ->label('Ville')
                    ->placeholder('Ex: Moroni, Mutsamudu, Fomboni...')
                    ->required(),

                TextInput::make('phone')
                    ->label('Téléphone')
                    ->placeholder('Ex: 333 00 00')
                    ->tel(),
            ]);
    }

    // Après la création, rattacher l'utilisateur au commerce
    protected function handleRegistration(array $data): \App\Models\Shop
    {
        $shop = \App\Models\Shop::create($data);

        // Rattacher l'utilisateur actuel comme membre du commerce
        $shop->members()->attach(auth()->user());

        return $shop;
    }
}