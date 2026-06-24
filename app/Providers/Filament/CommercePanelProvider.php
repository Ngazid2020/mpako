<?php

namespace App\Providers\Filament;

use App\Filament\Commerce\Pages\Dashboard;
use App\Models\Shop;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class CommercePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            // Identifiant unique
            ->id('commerce')

            // URL d'accès
            ->path('commerce')

            // Connexion + inscription (le commerçant peut s'inscrire seul)
            ->login(\App\Filament\Commerce\Pages\Auth\Login::class)
            ->registration(\App\Filament\Commerce\Pages\Auth\Register::class)

            // Couleurs — Bleu pour le commerce (confiance, sérieux)
            ->colors([
                'primary' => Color::hex('#0e6464'),
            ])

            // Logo et favicon
            ->brandLogo(asset('images/logo-filament-beez.png'))
            ->brandLogoHeight('2.75rem')
            ->favicon(asset('images/favicon-32.png'))
            ->viteTheme('resources/css/filament/commerce/theme.css')
            // ──────────────────────────────────────
            // MULTITENANCY : La clé de tout !
            // ──────────────────────────────────────
            ->tenant(Shop::class, ownershipRelationship: 'shop', slugAttribute: 'slug')

            // Le commerçant peut créer/modifier son commerce depuis le panel
            ->tenantRegistration(\App\Filament\Commerce\Pages\RegisterShop::class)
            ->tenantProfile(\App\Filament\Commerce\Pages\EditShop::class)

            // Découverte automatique des ressources du panel Commerce
            ->discoverResources(in: app_path('Filament/Commerce/Resources'), for: 'App\\Filament\\Commerce\\Resources')
            ->discoverPages(in: app_path('Filament/Commerce/Pages'), for: 'App\\Filament\\Commerce\\Pages')
            ->discoverWidgets(in: app_path('Filament/Commerce/Widgets'), for: 'App\\Filament\\Commerce\\Widgets')

            // Pages par défaut
            ->pages([
                Dashboard::class,
            ])
            // ✅ BOUTON : Retour vers Admin (si superadmin)
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
                fn(): string => Blade::render('@include("filament.components.switch-panel-button")')
            )
            // Middleware
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn(): string => Blade::render('@laravelPWA')
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn(): string => Blade::render('@include("filament.components.pwa-install-prompt")')
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn(): string => auth()->check() ? Blade::render('@include("filament.components.push-subscribe")') : ''
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn(): string => '<div x-data @open-print-window.window="window.open($event.detail.url, \'_blank\')"></div>'
            )
        ;
    }
}
