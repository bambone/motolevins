<?php

namespace App\Providers\Filament;

use App\Filament\Tenant\Pages\TenantDashboard;
use App\Filament\Tenant\Widgets\StatsOverviewWidget;
use App\Filament\Tenant\Widgets\TenantDashboardIntroWidget;
use App\Http\Middleware\EnsureTenantContext;
use App\Http\Middleware\EnsureTenantMembership;
use App\Http\Middleware\ResolveTenantFromDomain;
use App\Models\TenantSetting;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel
            ->renderHook(PanelsRenderHook::BODY_START, fn (): string => View::make('components.filament-access-denied-banner')->render())
            ->renderHook(PanelsRenderHook::STYLES_AFTER, function (): string {
                return Blade::render(<<<'HTML'
                    <style>
                        .fi-main-ctn { flex: 1 !important; min-width: 0 !important; }
                        .fi-main { max-width: none !important; width: 100% !important; }
                        .fi-motorcycle-cover-cell {
                            overflow: visible !important;
                            vertical-align: middle !important;
                        }
                        .fi-motorcycle-cover-cell .fi-ta-image img {
                            object-fit: cover;
                            border-radius: 0.25rem;
                            transition: transform 0.28s ease, box-shadow 0.28s ease;
                            transform-origin: left center;
                        }
                        .fi-motorcycle-cover-cell:hover .fi-ta-image img {
                            transform: scale(3.25);
                            z-index: 50;
                            position: relative;
                            box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.45);
                        }
                    </style>
                HTML
                );
            });

        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName(function (): string {
                $tenant = currentTenant();
                if ($tenant === null) {
                    return (string) config('app.name');
                }

                $name = trim((string) TenantSetting::getForTenant($tenant->id, 'general.site_name', ''));

                return $name !== '' ? $name : $tenant->defaultPublicSiteName();
            })
            ->homeUrl(function (): ?string {
                $tenant = currentTenant();
                if ($tenant === null) {
                    return null;
                }

                $stored = trim((string) TenantSetting::getForTenant($tenant->id, 'general.domain', ''));
                if ($stored !== '' && filter_var($stored, FILTER_VALIDATE_URL)) {
                    return $stored;
                }

                $fallback = $tenant->defaultPublicSiteUrl();
                if (filter_var($fallback, FILTER_VALIDATE_URL)) {
                    return $fallback;
                }

                return null;
            })
            ->login()
            ->globalSearch(false)
            ->maxContentWidth('full')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Tenant/Resources'), for: 'App\\Filament\\Tenant\\Resources')
            ->discoverPages(in: app_path('Filament/Tenant/Pages'), for: 'App\\Filament\\Tenant\\Pages')
            ->pages([
                TenantDashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Tenant/Widgets'), for: 'App\\Filament\\Tenant\\Widgets')
            ->widgets([
                TenantDashboardIntroWidget::class,
                StatsOverviewWidget::class,
                AccountWidget::class,
            ])
            ->middleware([
                ResolveTenantFromDomain::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                AuthenticateSession::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                EnsureTenantContext::class,
                EnsureTenantMembership::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
