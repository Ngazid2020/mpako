<?php

namespace App\Filament\Commerce\Pages;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\EditTenantProfile;

class EditShop extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return 'Paramètres du commerce';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Nom du commerce')
                    ->required()
                    ->maxLength(255),

                Select::make('island')
                    ->label('Île')
                    ->options([
                        'Grande Comore' => '🏝️ Grande Comore (Ngazidja)',
                        'Anjouan' => '🏝️ Anjouan (Ndzuani)',
                        'Mohéli' => '🏝️ Mohéli (Mwali)',
                        'Mayotte' => '🏝️ Mayotte (Maore)',
                    ])
                    ->required()
                    ->native(false),

                TextInput::make('city')
                    ->label('Ville')
                    ->required(),

                TextInput::make('address')
                    ->label('Adresse complète')
                    ->placeholder('Quartier, rue...'),

                TextInput::make('phone')
                    ->label('Téléphone')
                    ->tel(),

                TextInput::make('email')
                    ->label('Email du commerce')
                    ->email(),
            ]);
    }
}