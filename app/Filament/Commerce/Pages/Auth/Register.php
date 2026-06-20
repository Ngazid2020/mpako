<?php

namespace App\Filament\Commerce\Pages\Auth;

use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Database\Eloquent\Model;

class Register extends BaseRegister
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getNameFormComponent(),
                TextInput::make('phone')
                    ->label('Téléphone')
                    ->tel()
                    ->required()
                    ->unique(User::class)
                    ->maxLength(30)
                    ->placeholder('+269 321 00 00'),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ])
            ->statePath('data');
    }

    protected function handleRegistration(array $data): Model
    {
        return User::create([
            'name'     => $data['name'],
            'phone'    => $data['phone'],
            'password' => $data['password'],
        ]);
    }
}
