<?php

namespace App\Services\Seo;

use App\Models\Motorcycle;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\SeoMeta;
use App\Models\Tenant;
use DOMDocument;
use DOMElement;

final class SitemapGenerator
{
    public function __construct(
        private TenantCanonicalPublicBaseUrl $canonicalBase,
    ) {}

    public function generateXml(Tenant $tenant): string
    {
        $base = $this->canonicalBase->resolve($tenant);
        $urls = $this->collectEntries($tenant, $base);

        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = false;
        $urlset = $doc->createElementNS('http://www.sitemaps.org/schemas/sitemap/0.9', 'urlset');
        $doc->appendChild($urlset);

        foreach ($urls as $u) {
            $urlEl = $doc->createElement('url');
            $this->appendTextChild($doc, $urlEl, 'loc', $u['loc']);
            if (! empty($u['lastmod'])) {
                $this->appendTextChild($doc, $urlEl, 'lastmod', $u['lastmod']);
            }
            if (! empty($u['changefreq'])) {
                $this->appendTextChild($doc, $urlEl, 'changefreq', $u['changefreq']);
            }
            if (! empty($u['priority'])) {
                $this->appendTextChild($doc, $urlEl, 'priority', $u['priority']);
            }
            $urlset->appendChild($urlEl);
        }

        return $doc->saveXML() ?: '';
    }

    /**
     * @return list<array{loc: string, lastmod?: string, changefreq?: string, priority?: string}>
     */
    public function collectEntries(Tenant $tenant, string $base): array
    {
        $base = rtrim($base, '/');
        $seen = [];
        $out = [];

        $static = [
            ['path' => '/', 'changefreq' => 'daily', 'priority' => '1.0', 'lastmod' => null],
            ['path' => '/contacts', 'changefreq' => 'monthly', 'priority' => '0.7', 'lastmod' => null],
            ['path' => '/faq', 'changefreq' => 'monthly', 'priority' => '0.7', 'lastmod' => null],
            ['path' => '/about', 'changefreq' => 'monthly', 'priority' => '0.7', 'lastmod' => null],
            ['path' => '/motorcycles', 'changefreq' => 'weekly', 'priority' => '0.9', 'lastmod' => null],
            ['path' => '/prices', 'changefreq' => 'monthly', 'priority' => '0.7', 'lastmod' => null],
            ['path' => '/order', 'changefreq' => 'monthly', 'priority' => '0.6', 'lastmod' => null],
            ['path' => '/reviews', 'changefreq' => 'weekly', 'priority' => '0.7', 'lastmod' => null],
            ['path' => '/usloviya-arenda', 'changefreq' => 'yearly', 'priority' => '0.4', 'lastmod' => null],
            ['path' => '/booking', 'changefreq' => 'weekly', 'priority' => '0.8', 'lastmod' => null],
            ['path' => '/articles', 'changefreq' => 'weekly', 'priority' => '0.6', 'lastmod' => null],
            ['path' => '/delivery/anapa', 'changefreq' => 'yearly', 'priority' => '0.4', 'lastmod' => null],
            ['path' => '/delivery/gelendzhik', 'changefreq' => 'yearly', 'priority' => '0.4', 'lastmod' => null],
        ];

        foreach ($static as $row) {
            $loc = $row['path'] === '/' ? $base.'/' : $base.$row['path'];
            if (isset($seen[$loc])) {
                continue;
            }
            $seen[$loc] = true;
            $out[] = [
                'loc' => $loc,
                'changefreq' => $row['changefreq'],
                'priority' => $row['priority'],
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
            $pageTs = $page->updated_at;
            $secTs = $sectionMax[$page->id] ?? null;
            $last = $pageTs;
            if ($secTs !== null && $secTs > $last) {
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
                'lastmod' => $moto->updated_at?->format('Y-m-d'),
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

    private function appendTextChild(DOMDocument $doc, DOMElement $parent, string $name, string $value): void
    {
        $el = $doc->createElement($name);
        $el->appendChild($doc->createTextNode($value));
        $parent->appendChild($el);
    }
}
