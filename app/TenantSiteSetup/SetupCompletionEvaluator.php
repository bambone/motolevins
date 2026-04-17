<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

use App\ContactChannels\TenantContactChannelsStore;
use App\Models\Tenant;
use App\Models\TenantServiceProgram;
use App\Models\TenantSetting;
use App\Support\RussianPhone;

final class SetupCompletionEvaluator
{
    public const TAGLINE_MIN_LENGTH = 12;

    public function __construct(
        private readonly PageBuilderSetupInspector $pageInspector,
        private readonly TenantContactChannelsStore $contactChannelsStore,
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
            default => false,
        };
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
}
