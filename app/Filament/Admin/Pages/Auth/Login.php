<?php

namespace App\Filament\Admin\Pages\Auth;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

class Login extends BaseLogin
{
    public bool $showTwoFactor = false;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // ── Étape 1 : identifiants ─────────────────────────
                TextInput::make('phone')
                    ->label('Téléphone')
                    ->tel()
                    ->required()
                    ->visible(fn () => !$this->showTwoFactor)
                    ->autocomplete('tel')
                    ->autofocus()
                    ->extraInputAttributes(['tabindex' => 1]),

                $this->getPasswordFormComponent()
                    ->visible(fn () => !$this->showTwoFactor),

                $this->getRememberFormComponent()
                    ->visible(fn () => !$this->showTwoFactor),

                // ── Étape 2 : code TOTP ─────────────────────────────
                TextInput::make('otp_code')
                    ->label('Code d\'authentification')
                    ->placeholder('000000')
                    ->numeric()
                    ->minLength(6)
                    ->maxLength(12)
                    ->required()
                    ->visible(fn () => $this->showTwoFactor)
                    ->hint('Ouvrez Google Authenticator ou Authy.')
                    ->autofocus()
                    ->extraInputAttributes([
                        'tabindex'     => 1,
                        'inputmode'    => 'numeric',
                        'autocomplete' => 'one-time-code',
                    ]),

                Actions::make([
                    Action::make('back_to_credentials')
                        ->label('← Changer de compte')
                        ->link()
                        ->color('gray')
                        ->action(fn () => $this->showTwoFactor = false),
                ])->visible(fn () => $this->showTwoFactor),
            ])
            ->statePath('data');
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (\Filament\Exceptions\TooManyRequestsException $exception) {
            Notification::make()
                ->title('Trop de tentatives. Réessayez dans ' . $exception->secondsUntilAvailable . 's.')
                ->danger()
                ->send();
            return null;
        }

        $data = $this->form->getState();

        return $this->showTwoFactor
            ? $this->verifyTwoFactor($data)
            : $this->verifyCredentials($data);
    }

    private function verifyCredentials(array $data): ?LoginResponse
    {
        $user = User::where('phone', $data['phone'] ?? '')->first();

        if (!$user || !Hash::check($data['password'] ?? '', $user->password)) {
            Notification::make()
                ->title('Identifiants incorrects.')
                ->danger()
                ->send();
            return null;
        }

        if (!$user->canAccessPanel(Filament::getCurrentPanel())) {
            Notification::make()
                ->title('Accès non autorisé.')
                ->danger()
                ->send();
            return null;
        }

        if ($user->hasEnabledTwoFactor()) {
            session(['2fa_user_id' => $user->id, '2fa_remember' => $data['remember'] ?? false]);
            $this->showTwoFactor = true;
            return null;
        }

        Auth::login($user, $data['remember'] ?? false);
        session()->regenerate();

        return app(LoginResponse::class);
    }

    private function verifyTwoFactor(array $data): ?LoginResponse
    {
        $userId = session('2fa_user_id');
        $user   = User::find($userId);

        if (!$user) {
            $this->showTwoFactor = false;
            Notification::make()->title('Session expirée, reconnectez-vous.')->warning()->send();
            return null;
        }

        $code  = trim($data['otp_code'] ?? '');
        $valid = false;

        $g2fa = new Google2FA();

        if ($g2fa->verifyKey($user->two_factor_secret, $code)) {
            $valid = true;
        } else {
            $codes = $user->two_factor_recovery_codes ?? [];
            foreach ($codes as $i => $rc) {
                if (hash_equals(strtoupper($rc), strtoupper($code))) {
                    unset($codes[$i]);
                    $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->saveQuietly();
                    $valid = true;
                    break;
                }
            }
        }

        if (!$valid) {
            Notification::make()
                ->title('Code invalide. Vérifiez votre application.')
                ->danger()
                ->send();
            return null;
        }

        $remember = session('2fa_remember', false);
        session()->forget(['2fa_user_id', '2fa_remember']);

        Auth::login($user, $remember);
        session()->regenerate();

        return app(LoginResponse::class);
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        return ['phone' => $data['phone'] ?? '', 'password' => $data['password'] ?? ''];
    }
}
