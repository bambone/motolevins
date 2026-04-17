<?php

namespace App\Filament\Tenant\Widgets;

use App\Filament\Tenant\Pages\TenantSiteSetupCenterPage;
use App\TenantSiteSetup\SetupCapabilitySnapshot;
use App\TenantSiteSetup\SetupLaunchCtaSpec;
use App\TenantSiteSetup\SetupProfileRepository;
use App\TenantSiteSetup\SetupProgressService;
use App\TenantSiteSetup\SetupSessionService;
use App\TenantSiteSetup\SetupTracksResolver;
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

    public function getSessionStatusLabelProperty(): string
    {
        $tenant = currentTenant();
        $user = Auth::user();
        if ($tenant === null || $user === null) {
            return '';
        }
        $svc = app(SetupSessionService::class);
        if ($svc->pausedSession($tenant, $user) !== null) {
            return 'На паузе';
        }
        if ($svc->activeSession($tenant, $user) !== null) {
            return 'Базовый запуск в процессе';
        }

        return '';
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getNextPendingItemProperty(): ?array
    {
        $summary = $this->summary;
        if ($summary === null) {
            return null;
        }
        $items = $summary['next_pending_items'] ?? [];
        if (! is_array($items) || $items === []) {
            return null;
        }
        $first = $items[0];

        return is_array($first) ? $first : null;
    }

    public function getRemainingCountProperty(): int
    {
        $summary = $this->summary;
        if ($summary === null) {
            return 0;
        }
        $a = (int) ($summary['applicable_count'] ?? 0);
        $c = (int) ($summary['completed_count'] ?? 0);

        return max(0, $a - $c);
    }

    public function getWhatsNextHintProperty(): string
    {
        $tenant = currentTenant();
        $user = Auth::user();
        if ($tenant === null || $user === null) {
            return '';
        }
        $profile = app(SetupProfileRepository::class)->getMerged((int) $tenant->id);
        $snap = SetupCapabilitySnapshot::capture($tenant, $user);
        $tracks = app(SetupTracksResolver::class)->resolve($tenant, $user, $profile, $snap);
        if ($tracks->suppressedTracks !== []) {
            return 'Часть направлений отключена по модулям или правам — их можно подключить позже в кабинете.';
        }

        return 'Дальше — расширенный контур и другие разделы панели по мере необходимости.';
    }
}
