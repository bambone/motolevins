<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

use App\ContactChannels\TenantContactChannelsStore;
use App\Models\Tenant;
use App\Models\TenantServiceProgram;
use App\Models\TenantSetting;
use App\Services\Analytics\AnalyticsSettingsPersistence;
use App\Support\Analytics\AnalyticsSettingsData;
use App\Support\RussianPhone;

final class SetupCompletionEvaluator
{
    public const TAGLINE_MIN_LENGTH = 12;

    public function __construct(
        private readonly PageBuilderSetupInspector $pageInspector,
        private readonly TenantContactChannelsStore $contactChannelsStore,
        private readonly AnalyticsSettingsPersistence $analyticsPersistence,
    ) {}

    public function isComplete(Tenant $tenant, SetupItemDefinition $def): bool
    {
        return match ($def->key) {
            'settings.site_name' => $this->siteNameComplete($tenant),
            'settings.logo' => $this->logoComplete($tenant),
            'settings.tagline_or_short_description' => $this->taglineComplete($tenant),
            'contact_channels.primary_phone' => $this->primaryPhoneComplete($tenant),
            'contact_channels.preferred_contact_channel' => $this->preferredChannelsComplete($tenant),
            'programs.first_published_program' => $this->firstProgramComplete($tenant),
            'pages.home.hero_title' => $this->pageInspector->heroHeadingFilled($tenant),
            'pages.home.hero_cta_or_contact_block' => $this->pageInspector->hasCtaOrContactBlock($tenant),
            'settings.favicon' => $this->faviconComplete($tenant),
            'settings.analytics_counters' => $this->analyticsCountersComplete($tenant),
            'settings.branding_hero_social_image' => $this->brandingHeroSocialComplete($tenant),
            'programs.two_visible_programs' => $this->twoVisibleProgramsComplete($tenant),
            'settings.public_canonical_url' => $this->publicCanonicalUrlComplete($tenant),
            'setup.booking_notifications_brief' => $this->bookingNotificationsBriefComplete($tenant),
            default => false,
        };
    }

    /**
     * Шаг закрывается по **актуальным** артефактам мастера (пресет/получатели/правила с маркером),
     * а не только по факту «когда-то нажали Применить».
     */
    private function bookingNotificationsBriefComplete(Tenant $tenant): bool
    {
        return BookingNotificationsBriefingWizardMarkers::hasAnyWizardArtifact($tenant);
    }

    private function siteNameComplete(Tenant $tenant): bool
    {
        $name = trim((string) TenantSetting::getForTenant($tenant->id, 'general.site_name', ''));

        return $name !== '';
    }

    private function logoComplete(Tenant $tenant): bool
    {
        $logo = trim((string) TenantSetting::getForTenant($tenant->id, 'branding.logo', ''));
        $path = trim((string) TenantSetting::getForTenant($tenant->id, 'branding.logo_path', ''));

        return $logo !== '' || $path !== '';
    }

    private function taglineComplete(Tenant $tenant): bool
    {
        $text = trim((string) TenantSetting::getForTenant($tenant->id, 'general.short_description', ''));

        return mb_strlen($text) >= self::TAGLINE_MIN_LENGTH;
    }

    private function primaryPhoneComplete(Tenant $tenant): bool
    {
        $raw = (string) TenantSetting::getForTenant($tenant->id, 'contacts.phone', '');

        return RussianPhone::normalize($raw) !== null;
    }

    private function preferredChannelsComplete(Tenant $tenant): bool
    {
        $ids = $this->contactChannelsStore->allowedPreferredChannelIds((int) $tenant->id);

        return count($ids) >= 2;
    }

    private function firstProgramComplete(Tenant $tenant): bool
    {
        return TenantServiceProgram::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_visible', true)
            ->whereRaw('TRIM(COALESCE(title, "")) <> ""')
            ->whereRaw('TRIM(COALESCE(teaser, "")) <> "" OR TRIM(COALESCE(description, "")) <> ""')
            ->exists();
    }

    private function faviconComplete(Tenant $tenant): bool
    {
        $favicon = trim((string) TenantSetting::getForTenant($tenant->id, 'branding.favicon', ''));
        $path = trim((string) TenantSetting::getForTenant($tenant->id, 'branding.favicon_path', ''));

        return $favicon !== '' || $path !== '';
    }

    /**
     * Пункт «аналитика» закрывается при **любом** валидном подключённом счётчике (Метрика и/или GA4),
     * см. {@see AnalyticsSettingsData::isEmpty()} — не «полная настройка всех тумблеров».
     */
    private function analyticsCountersComplete(Tenant $tenant): bool
    {
        $data = $this->analyticsPersistence->load((int) $tenant->id);

        return ! $data->isEmpty();
    }

    private function brandingHeroSocialComplete(Tenant $tenant): bool
    {
        $hero = trim((string) TenantSetting::getForTenant($tenant->id, 'branding.hero', ''));
        $path = trim((string) TenantSetting::getForTenant($tenant->id, 'branding.hero_path', ''));

        return $hero !== '' || $path !== '';
    }

    private function twoVisibleProgramsComplete(Tenant $tenant): bool
    {
        return TenantServiceProgram::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_visible', true)
            ->count() >= 2;
    }

    private function publicCanonicalUrlComplete(Tenant $tenant): bool
    {
        $raw = trim((string) TenantSetting::getForTenant($tenant->id, 'general.domain', ''));

        return $raw !== '' && filter_var($raw, FILTER_VALIDATE_URL) !== false;
    }
}
