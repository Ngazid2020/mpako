<?php

namespace App\Filament\Commerce\Pages\Auth;

use App\Models\User;
use App\Services\ShopOnboarding;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

/**
 * Inscription en libre-service (bouton « Commencer » de la page d'accueil) :
 * le commerçant crée son compte ET sa boutique en une fois, avec un mois d'essai gratuit.
 *
 * Le flux parent de Filament est conservé : limitation des tentatives, transaction,
 * connexion puis régénération de session, redirection vers le tableau de bord de la boutique.
 */
class Register extends BaseRegister
{
    public function getHeading(): string
    {
        return 'Créez votre boutique';
    }

    public function getTitle(): string
    {
        return 'Créer ma boutique';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Le sous-titre de la page est réservé par Filament au lien « Se connecter »
                Placeholder::make('trial')
                    ->hiddenLabel()
                    ->content(new HtmlString(
                        '<div class="rounded-lg bg-primary-50 px-3 py-2 text-sm text-primary-900 ring-1 ring-primary-200">'
                        . "🎁 <strong>1 mois d'essai gratuit</strong> : toutes les fonctionnalités, aucun paiement à l'inscription."
                        . '</div>'
                    )),

                Section::make('Votre compte')
                    ->schema([
                        $this->getNameFormComponent()->label('Votre nom'),
                        TextInput::make('phone')
                            ->label('Téléphone')
                            ->helperText('Il vous servira à vous connecter.')
                            ->tel()
                            ->required()
                            ->unique(User::class)
                            ->maxLength(30)
                            ->placeholder('+269 321 00 00'),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                    ]),

                Section::make('Votre boutique')
                    ->schema([
                        TextInput::make('shop_name')
                            ->label('Nom de la boutique')
                            ->placeholder('Ex : Boutique Ali, Épicerie du coin…')
                            ->required()
                            ->maxLength(255),
                        Select::make('shop_island')
                            ->label('Île')
                            ->options([
                                'Grande Comore' => 'Grande Comore (Ngazidja)',
                                'Anjouan'       => 'Anjouan (Ndzuani)',
                                'Mohéli'        => 'Mohéli (Mwali)',
                            ])
                            ->required()
                            ->native(false),
                        TextInput::make('shop_city')
                            ->label('Ville')
                            ->placeholder('Ex : Moroni, Mutsamudu, Fomboni…')
                            ->required()
                            ->maxLength(255),
                    ]),

            ])
            ->statePath('data');
    }

    protected function handleRegistration(array $data): Model
    {
        $user = User::create([
            'name'        => $data['name'],
            'phone'       => $data['phone'],
            'password'    => $data['password'],
            // Inscription en libre-service : compte actif immédiatement.
            // Le super-admin peut toujours le désactiver (is_approved = false).
            'is_approved' => true,
        ]);

        app(ShopOnboarding::class)->createShop($user, [
            'name'   => $data['shop_name'],
            'island' => $data['shop_island'],
            'city'   => $data['shop_city'],
        ]);

        return $user;
    }
}
