<?php

namespace App\Filament\Commerce\Pages;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorSetup extends Page
{
    protected static string $view = 'filament.commerce.pages.two-factor-setup';

    protected static ?string $navigationLabel = 'Sécurité (2FA)';
    protected static ?string $navigationIcon  = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Mon compte';
    protected static ?int    $navigationSort  = 90;

    // 'status' | 'setup' | 'recovery'
    public string  $pageState    = 'status';
    public ?string $qrCodeSvg   = null;
    public ?string $secretKey   = null;
    public string  $confirmCode = '';
    public array   $recoveryCodes = [];

    // ─────────────────────────────────────────────
    // Actions
    // ─────────────────────────────────────────────

    public function startSetup(): void
    {
        $secret = (new Google2FA())->generateSecretKey();
        session(['2fa_setup_secret' => $secret]);

        $this->secretKey   = $secret;
        $this->qrCodeSvg   = $this->buildQrSvg($secret);
        $this->confirmCode = '';
        $this->pageState   = 'setup';
    }

    public function confirmSetup(): void
    {
        $secret = session('2fa_setup_secret');

        if (!$secret) {
            $this->pageState = 'status';
            return;
        }

        $this->validate(['confirmCode' => ['required', 'digits:6']]);

        if (!(new Google2FA())->verifyKey($secret, $this->confirmCode)) {
            $this->addError('confirmCode', 'Code invalide. Vérifiez votre application et réessayez.');
            return;
        }

        $codes = $this->makeRecoveryCodes();

        auth()->user()->forceFill([
            'two_factor_secret'         => $secret,
            'two_factor_recovery_codes' => $codes,
            'two_factor_confirmed_at'   => now(),
        ])->save();

        session()->forget('2fa_setup_secret');

        $this->recoveryCodes = $codes;
        $this->secretKey     = null;
        $this->qrCodeSvg     = null;
        $this->confirmCode   = '';
        $this->pageState     = 'recovery';

        Notification::make()->title('2FA activé avec succès !')->success()->send();
    }

    public function disable(): void
    {
        auth()->user()->forceFill([
            'two_factor_secret'         => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at'   => null,
        ])->save();

        $this->pageState = 'status';

        Notification::make()->title('Authentification 2FA désactivée.')->success()->send();
    }

    public function cancelSetup(): void
    {
        session()->forget('2fa_setup_secret');

        $this->pageState   = 'status';
        $this->secretKey   = null;
        $this->qrCodeSvg   = null;
        $this->confirmCode = '';
    }

    // ─────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────

    private function buildQrSvg(string $secret): string
    {
        $user = auth()->user();
        $url  = (new Google2FA())->getQRCodeUrl(
            config('app.name'),
            $user->phone ?? $user->name,
            $secret
        );

        $renderer = new ImageRenderer(
            new RendererStyle(220),
            new SvgImageBackEnd()
        );

        $svg = (new Writer($renderer))->writeString($url);

        // Strip XML declaration and DOCTYPE for inline embedding
        $svg = preg_replace('/^<\?xml[^?]*\?>\s*/i', '', $svg);
        $svg = preg_replace('/<!DOCTYPE[^>]*>\s*/i', '', $svg);

        return $svg;
    }

    private function makeRecoveryCodes(): array
    {
        return collect(range(1, 8))->map(function () {
            $a = strtoupper(substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(6))), 0, 5));
            $b = strtoupper(substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(6))), 0, 5));
            return "{$a}-{$b}";
        })->toArray();
    }
}
