<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

use App\ContactChannels\TenantContactChannelsStore;
use App\Models\Tenant;
use App\Models\TenantServiceProgram;
use App\Models\TenantSetting;
use App\Support\RussianPhone;

final class SetupValueSnapshotResolver
{
    public function __construct(
        private readonly PageBuilderSetupInspector $pageInspector,
        private readonly TenantContactChannelsStore $contactChannelsStore,
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
            default => '—',
        };
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
}
