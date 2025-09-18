<?php

namespace App\Providers\Filament;

use App\Filament\Pages\SubmitApplication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Enums\ThemeMode;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationItem;
use App\Filament\Pages\Dashboard\ThankYou;
use App\Http\Middleware\RefreshGoogleDriveToken;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Blade;

class DashboardPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('dashboard')
            ->path('dashboard')
            ->login()
            ->brandName('Leviev Foundation')
            ->brandLogo(fn() => view('components.filament.admin.brand'))
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
                fn(): string => '<a href="https://levievfoundation.org/grant-program/" target="_blank" class="px-4 py-1 text-sm font-medium text-gray-700 hover:text-primary-600">Instructions/Eligibility</a>'
            )
            ->homeUrl("https://levievfoundation.org")
            ->favicon(asset('images/logo.jpg'))
            ->registration()
            ->emailVerification()
            ->passwordReset()
            ->sidebarWidth('13rem')
            ->defaultThemeMode(ThemeMode::Light)
            ->colors([
                'primary' => "#c29a6f",
            ])
            ->sidebarFullyCollapsibleOnDesktop()
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full')
            ->discoverResources(in: app_path('Filament/Resources/Dashboard'), for: 'App\\Filament\\Resources\\Dashboard')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                ThankYou::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([])
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
            ])->tenant(null);
    }

    public function boot(): void
    {
        FilamentAsset::register([
            Css::make('custom-stylesheet', asset('css/custom.css')),
            Js::make('custom-script', asset('js/custom.js')),
        ]);
    }
}
