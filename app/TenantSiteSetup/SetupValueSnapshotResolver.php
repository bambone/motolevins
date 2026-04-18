<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

use App\ContactChannels\TenantContactChannelsStore;
use App\Models\Tenant;
use App\Models\TenantServiceProgram;
use App\Models\TenantSetting;
use App\Services\Analytics\AnalyticsSettingsPersistence;
use App\Support\RussianPhone;
use App\TenantSiteSetup\BookingNotificationsBriefingWizardMarkers;
use App\TenantSiteSetup\BookingNotificationsQuestionnaireRepository;

final class SetupValueSnapshotResolver
{
    public function __construct(
        private readonly PageBuilderSetupInspector $pageInspector,
        private readonly TenantContactChannelsStore $contactChannelsStore,
        private readonly AnalyticsSettingsPersistence $analyticsPersistence,
    ) {}

    public function snapshot(Tenant $tenant, SetupItemDefinition $def): string
    {
        return match ($def->key) {
            'settings.site_name' => mb_substr(trim((string) TenantSetting::getForTenant($tenant->id, 'general.site_name', '')), 0, 120) ?: '—',
            'settings.logo' => $this->logoComplete($tenant) ? 'загружен' : '—',
            'settings.tagline_or_short_description' => mb_substr(trim((string) TenantSetting::getForTenant($tenant->id, 'general.short_description', '')), 0, 120) ?: '—',
            'contact_channels.primary_phone' => RussianPhone::toMasked((string) TenantSetting::getForTenant($tenant->id, 'contacts.phone', '')) ?: '—',
            'contact_channels.preferred_contact_channel' => (string) count($this->contactChannelsStore->allowedPreferredChannelIds((int) $tenant->id)).' канал(ов)',
            'programs.first_published_program' => $this->programSnapshot($tenant),
            'pages.home.hero_title' => $this->pageInspector->heroHeadingSnapshot($tenant),
            'pages.home.hero_cta_or_contact_block' => $this->pageInspector->ctaOrContactSnapshot($tenant),
            'settings.favicon' => $this->faviconSnapshot($tenant),
            'settings.analytics_counters' => $this->analyticsSnapshot($tenant),
            'settings.branding_hero_social_image' => $this->brandingHeroSnapshot($tenant),
            'programs.two_visible_programs' => $this->twoProgramsSnapshot($tenant),
            'settings.public_canonical_url' => $this->canonicalUrlSnapshot($tenant),
            'setup.booking_notifications_brief' => $this->bookingNotificationsBriefSnapshot($tenant),
            default => '—',
        };
    }

    private function bookingNotificationsBriefSnapshot(Tenant $tenant): string
    {
        $live = BookingNotificationsBriefingWizardMarkers::snapshotLine($tenant);
        $applied = app(BookingNotificationsQuestionnaireRepository::class)->appliedAt((int) $tenant->id);

        if (! BookingNotificationsBriefingWizardMarkers::hasAnyWizardArtifact($tenant)) {
            if ($applied !== null) {
                return 'применение было; данные мастера удалены';
            }

            return $live;
        }

        return $live;
    }

    private function logoComplete(Tenant $tenant): bool
    {
        $logo = trim((string) TenantSetting::getForTenant($tenant->id, 'branding.logo', ''));
        $path = trim((string) TenantSetting::getForTenant($tenant->id, 'branding.logo_path', ''));

        return $logo !== '' || $path !== '';
    }

    private function programSnapshot(Tenant $tenant): string
    {
        $n = TenantServiceProgram::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_visible', true)
            ->count();

        return $n > 0 ? $n.' опубликовано' : '—';
    }

    private function faviconSnapshot(Tenant $tenant): string
    {
        $favicon = trim((string) TenantSetting::getForTenant($tenant->id, 'branding.favicon', ''));
        $path = trim((string) TenantSetting::getForTenant($tenant->id, 'branding.favicon_path', ''));

        return ($favicon !== '' || $path !== '') ? 'задано' : '—';
    }

    private function analyticsSnapshot(Tenant $tenant): string
    {
        $data = $this->analyticsPersistence->load((int) $tenant->id);
        if ($data->isEmpty()) {
            return 'не подключено';
        }
        $parts = [];
        if ($data->hasRenderableYandex()) {
            $parts[] = 'Метрика';
        }
        if ($data->hasRenderableGa4()) {
            $parts[] = 'GA4';
        }

        return $parts !== [] ? implode(', ', $parts) : 'частично';
    }

    private function brandingHeroSnapshot(Tenant $tenant): string
    {
        $hero = trim((string) TenantSetting::getForTenant($tenant->id, 'branding.hero', ''));
        $path = trim((string) TenantSetting::getForTenant($tenant->id, 'branding.hero_path', ''));

        return ($hero !== '' || $path !== '') ? 'задано' : '—';
    }

    private function twoProgramsSnapshot(Tenant $tenant): string
    {
        $n = TenantServiceProgram::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_visible', true)
            ->count();

        return (string) $n.' витринных';
    }

    private function canonicalUrlSnapshot(Tenant $tenant): string
    {
        $raw = trim((string) TenantSetting::getForTenant($tenant->id, 'general.domain', ''));

        return $raw !== '' ? mb_substr($raw, 0, 80) : '—';
    }
}
