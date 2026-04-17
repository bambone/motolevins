<?php

namespace App\Filament\Tenant\Widgets;

use App\Filament\Tenant\Pages\TenantSiteSetupCenterPage;
use App\TenantSiteSetup\SetupLaunchCtaSpec;
use App\TenantSiteSetup\SetupProgressService;
use App\TenantSiteSetup\TenantSiteSetupFeature;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class SiteReadinessWidget extends Widget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.tenant.widgets.site-readiness-widget';

    public static function canView(): bool
    {
        if (! TenantSiteSetupFeature::enabled()) {
            return false;
        }

        return Gate::allows('manage_settings') && currentTenant() !== null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getSummaryProperty(): ?array
    {
        $tenant = currentTenant();
        if ($tenant === null) {
            return null;
        }

        return app(SetupProgressService::class)->summary($tenant);
    }

    public function getCenterUrlProperty(): ?string
    {
        return TenantSiteSetupCenterPage::getUrl();
    }

    /**
     * @return array{label: string, href: string}|null
     */
    public function getPrimaryCtaProperty(): ?array
    {
        $tenant = currentTenant();
        $user = Auth::user();
        $summary = $this->summary;
        if ($tenant === null || $user === null || $summary === null) {
            return null;
        }

        return app(SetupLaunchCtaSpec::class)->dashboardPrimary(
            $tenant,
            $user,
            $summary,
            TenantSiteSetupCenterPage::getUrl(),
        );
    }
}
