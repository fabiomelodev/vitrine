<?php

namespace App\Providers\Filament;

use App\Filament\Painel\Pages\Auth\Register;
use App\Filament\Painel\Pages\Tenancy\EditStoreProfile;
use App\Filament\Painel\Pages\Tenancy\RegisterStore;
use App\Http\Middleware\EnsureStoreHasSubscription;
use App\Http\Middleware\EnsureStoreIsActive;
use App\Models\Store;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Painel do lojista: cada usuário gerencia a(s) sua(s) loja(s).
 * URL: /painel/{slug-da-loja}/...
 */
class PainelPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('painel')
            ->path('painel')
            ->login()
            ->registration(Register::class)
            ->passwordReset()
            ->profile()
            ->brandName('Provador')
            ->colors([
                'primary' => Color::Stone,
            ])
            ->tenant(Store::class, slugAttribute: 'slug')
            ->tenantRegistration(RegisterStore::class)
            ->tenantProfile(EditStoreProfile::class)
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->tenantMiddleware([
                EnsureStoreIsActive::class,
            ], isPersistent: true)
            ->tenantMiddleware([
                EnsureStoreHasSubscription::class,
            ])
            ->renderHook(
                PanelsRenderHook::CONTENT_START,
                fn () => view('filament.painel.subscription-banner'),
            )
            ->renderHook(
                PanelsRenderHook::TENANT_MENU_AFTER,
                fn () => view('filament.painel.storefront-button'),
            )
            ->discoverResources(in: app_path('Filament/Painel/Resources'), for: 'App\Filament\Painel\Resources')
            ->discoverPages(in: app_path('Filament/Painel/Pages'), for: 'App\Filament\Painel\Pages')
            ->discoverWidgets(in: app_path('Filament/Painel/Widgets'), for: 'App\Filament\Painel\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
