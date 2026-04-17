<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

use App\Models\Page;
use App\Models\PageSection;
use App\Models\Tenant;

/**
 * Builder-first home page inspection for completion rules (no TenantSetting for page body).
 */
final class PageBuilderSetupInspector
{
    /** @var list<string> */
    private const CTA_OR_CONTACT_SECTION_TYPES = [
        'cta',
        'expert_lead_form',
        'contacts_info',
        'contact_inquiry',
    ];

    public function homePage(Tenant $tenant): ?Page
    {
        return Page::query()
            ->where('tenant_id', $tenant->id)
            ->where('slug', 'home')
            ->first();
    }

    public function heroHeadingFilled(Tenant $tenant): bool
    {
        $page = $this->homePage($tenant);
        if ($page === null) {
            return false;
        }

        $hero = PageSection::query()
            ->where('page_id', $page->id)
            ->where('section_type', 'hero')
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->first();

        if ($hero === null) {
            return false;
        }

        $data = $hero->data_json;
        if (! is_array($data)) {
            return false;
        }

        $heading = trim((string) ($data['heading'] ?? ''));

        return $heading !== '';
    }

    public function heroHeadingSnapshot(Tenant $tenant): string
    {
        $page = $this->homePage($tenant);
        if ($page === null) {
            return '—';
        }

        $hero = PageSection::query()
            ->where('page_id', $page->id)
            ->where('section_type', 'hero')
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->first();

        if ($hero === null) {
            return '—';
        }

        $data = $hero->data_json;
        if (! is_array($data)) {
            return '—';
        }

        $h = trim((string) ($data['heading'] ?? ''));

        return $h !== '' ? mb_substr($h, 0, 80) : '—';
    }

    public function hasCtaOrContactBlock(Tenant $tenant): bool
    {
        $page = $this->homePage($tenant);
        if ($page === null) {
            return false;
        }

        return PageSection::query()
            ->where('page_id', $page->id)
            ->whereIn('section_type', self::CTA_OR_CONTACT_SECTION_TYPES)
            ->where('is_visible', true)
            ->exists();
    }

    public function ctaOrContactSnapshot(Tenant $tenant): string
    {
        $page = $this->homePage($tenant);
        if ($page === null) {
            return '—';
        }

        $types = PageSection::query()
            ->where('page_id', $page->id)
            ->whereIn('section_type', self::CTA_OR_CONTACT_SECTION_TYPES)
            ->where('is_visible', true)
            ->pluck('section_type')
            ->unique()
            ->values()
            ->all();

        return $types === [] ? '—' : implode(', ', $types);
    }
}
