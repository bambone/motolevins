<?php

namespace App\Services\Seo;

use App\Models\LocationLandingPage;
use App\Models\Motorcycle;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\SeoLandingPage;
use App\Models\SeoMeta;
use App\Models\Tenant;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Aggregates sitemap URL rows for a tenant (static config + CMS pages + catalog).
 */
final class SitemapUrlProvider
{
    public function __construct(
        private TenantCanonicalPublicBaseUrl $canonicalBase,
    ) {}

    /**
     * @return list<array{loc: string, lastmod?: string, changefreq?: string, priority?: string}>
     */
    public function collectEntries(Tenant $tenant): array
    {
        $base = rtrim($this->canonicalBase->resolve($tenant), '/');
        $seen = [];
        $out = [];

        $static = config('seo_sitemap.static_paths', []);
        $static = is_array($static) ? $static : [];

        foreach ($static as $row) {
            if (! is_array($row) || ! isset($row['path'])) {
                continue;
            }
            $path = (string) $row['path'];
            $changefreq = isset($row['changefreq']) ? (string) $row['changefreq'] : 'monthly';
            $priority = isset($row['priority']) ? (string) $row['priority'] : '0.5';
            $loc = $path === '/' ? $base.'/' : $base.$path;
            if (isset($seen[$loc])) {
                continue;
            }
            $seen[$loc] = true;
            $out[] = [
                'loc' => $loc,
                'changefreq' => $changefreq,
                'priority' => $priority,
            ];
        }

        $pages = Page::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'published')
            ->where('slug', '!=', 'home')
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->with('seoMeta')
            ->get();

        $pageIds = $pages->pluck('id')->all();
        $sectionMax = [];
        if ($pageIds !== []) {
            $sectionMax = PageSection::query()
                ->where('tenant_id', $tenant->id)
                ->whereIn('page_id', $pageIds)
                ->selectRaw('page_id, MAX(updated_at) as m')
                ->groupBy('page_id')
                ->pluck('m', 'page_id');
        }

        foreach ($pages as $page) {
            if (! $this->isSeoIndexable($page->seoMeta)) {
                continue;
            }
            $loc = $base.'/'.ltrim((string) $page->slug, '/');
            if (isset($seen[$loc])) {
                continue;
            }
            $seen[$loc] = true;
            $pageTs = self::toCarbonOrNull($page->updated_at);
            $secTs = self::toCarbonOrNull($sectionMax[$page->id] ?? null);
            $last = $pageTs;
            if ($secTs !== null && ($last === null || $secTs->gt($last))) {
                $last = $secTs;
            }
            $out[] = [
                'loc' => $loc,
                'changefreq' => 'weekly',
                'priority' => '0.8',
                'lastmod' => $last?->format('Y-m-d'),
            ];
        }

        $motorcycles = Motorcycle::query()
            ->where('tenant_id', $tenant->id)
            ->where('show_in_catalog', true)
            ->where('status', 'available')
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->with('seoMeta')
            ->get();

        foreach ($motorcycles as $moto) {
            if (! $this->isSeoIndexable($moto->seoMeta)) {
                continue;
            }
            $loc = $base.'/moto/'.rawurlencode((string) $moto->slug);
            if (isset($seen[$loc])) {
                continue;
            }
            $seen[$loc] = true;
            $out[] = [
                'loc' => $loc,
                'changefreq' => 'weekly',
                'priority' => '0.7',
                'lastmod' => self::toCarbonOrNull($moto->updated_at)?->format('Y-m-d'),
            ];
        }

        $locations = LocationLandingPage::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_published', true)
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->with('seoMeta')
            ->get();

        foreach ($locations as $locRow) {
            if (! $this->isSeoIndexable($locRow->seoMeta)) {
                continue;
            }
            $slug = trim((string) $locRow->slug);
            $loc = $base.'/locations/'.rawurlencode($slug);
            if (isset($seen[$loc])) {
                continue;
            }
            $seen[$loc] = true;
            $out[] = [
                'loc' => $loc,
                'changefreq' => 'monthly',
                'priority' => '0.6',
                'lastmod' => self::toCarbonOrNull($locRow->updated_at)?->format('Y-m-d'),
            ];
        }

        $landings = SeoLandingPage::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_published', true)
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->with('seoMeta')
            ->get();

        foreach ($landings as $landing) {
            if (! $this->isSeoIndexable($landing->seoMeta)) {
                continue;
            }
            $slug = trim((string) $landing->slug);
            $loc = $base.'/landings/'.rawurlencode($slug);
            if (isset($seen[$loc])) {
                continue;
            }
            $seen[$loc] = true;
            $out[] = [
                'loc' => $loc,
                'changefreq' => 'monthly',
                'priority' => '0.55',
                'lastmod' => self::toCarbonOrNull($landing->updated_at)?->format('Y-m-d'),
            ];
        }

        return $out;
    }

    private function isSeoIndexable(?SeoMeta $meta): bool
    {
        if ($meta === null) {
            return true;
        }
        if (! $meta->is_indexable) {
            return false;
        }
        $robots = strtolower((string) ($meta->robots ?? ''));

        return ! str_contains($robots, 'noindex');
    }

    private static function toCarbonOrNull(mixed $value): ?Carbon
    {
        if ($value === null) {
            return null;
        }
        if ($value instanceof CarbonInterface) {
            return Carbon::instance($value);
        }
        if (is_string($value) && $value !== '') {
            try {
                return Carbon::parse($value);
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }
}
