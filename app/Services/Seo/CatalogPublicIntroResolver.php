<?php

namespace App\Services\Seo;

use App\Models\Page;
use App\Models\PageSection;
use App\Models\Tenant;

/**
 * CMS-driven SEO intro for /motorcycles (Page slug {@code motorcycles}, section_key {@code catalog_seo_intro}).
 */
final class CatalogPublicIntroResolver
{
    public function resolveSection(Tenant $tenant): ?PageSection
    {
        $page = Page::query()
            ->where('tenant_id', $tenant->id)
            ->where('slug', 'motorcycles')
            ->where('status', 'published')
            ->first();

        if ($page === null) {
            return null;
        }

        return $page->sections()
            ->where('status', 'published')
            ->where('is_visible', true)
            ->where('section_key', 'catalog_seo_intro')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();
    }
}
