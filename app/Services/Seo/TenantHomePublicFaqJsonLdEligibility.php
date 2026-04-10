<?php

namespace App\Services\Seo;

use App\Models\Faq;
use App\Models\Page;
use App\Models\Tenant;
use Illuminate\Support\Collection;

/**
 * FAQPage JSON-LD on home is allowed only when the same FAQ entities are rendered in the visible FAQ block.
 */
final class TenantHomePublicFaqJsonLdEligibility
{
    /**
     * @return Collection<int, Faq>|null null = do not emit FAQPage on home
     */
    public function eligiblePublishedFaqsForHome(Tenant $tenant): ?Collection
    {
        $page = Page::query()
            ->where('tenant_id', $tenant->id)
            ->where('slug', 'home')
            ->where('status', 'published')
            ->first();

        if ($page === null) {
            return null;
        }

        $hasVisibleFaqBlock = $page->sections()
            ->where('status', 'published')
            ->where('is_visible', true)
            ->where('section_key', 'faq_block')
            ->exists();

        if (! $hasVisibleFaqBlock) {
            return null;
        }

        $faqs = Faq::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'published')
            ->where('show_on_home', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($faqs->isEmpty()) {
            return null;
        }

        return $faqs;
    }
}
