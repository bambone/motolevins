<?php

namespace App\Services\Seo;

use App\Models\Motorcycle;
use App\Models\SeoMeta;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

final class JsonLdGenerator
{
    public function __construct(
        private TenantCanonicalPublicBaseUrl $canonicalBase,
        private TenantSeoJsonLdOverrideMerger $overrideMerger,
        private FallbackSeoGenerator $fallback,
    ) {}

    /**
     * @param  list<array{url: string, name: string}>|null  $itemListEntries
     * @return list<array<string, mixed>>
     */
    public function buildGraph(
        Tenant $tenant,
        string $routeName,
        ?Model $model,
        ?SeoMeta $seo,
        string $canonicalUrl,
        ?array $itemListEntries = null,
    ): array {
        $base = rtrim($this->canonicalBase->resolve($tenant), '/');
        $siteUrl = $base.'/';

        $graph = [];

        if ($routeName === 'home') {
            $graph = $this->organizationAndWebSite($tenant, $siteUrl);
        } elseif ($model instanceof Motorcycle && in_array($routeName, ['motorcycle.show', 'booking.show'], true)) {
            $graph = [$this->productFromMotorcycle($model, $canonicalUrl)];
        } elseif ($routeName === 'motorcycles.index' && is_array($itemListEntries) && $itemListEntries !== []) {
            $graph = [$this->itemList($itemListEntries)];
        }

        return $this->overrideMerger->merge($graph, $seo);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function organizationAndWebSite(Tenant $tenant, string $siteUrl): array
    {
        $name = $this->fallback->siteName($tenant);

        return [
            [
                '@type' => 'Organization',
                'name' => $name,
                'url' => $siteUrl,
            ],
            [
                '@type' => 'WebSite',
                'name' => $name,
                'url' => $siteUrl,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function productFromMotorcycle(Motorcycle $m, string $canonicalUrl): array
    {
        $name = trim((string) $m->name) ?: (string) $m->slug;
        $desc = '';
        if (TenantSeoMerge::isFilled($m->short_description)) {
            $desc = trim(strip_tags((string) $m->short_description));
        } elseif (TenantSeoMerge::isFilled($m->full_description)) {
            $desc = trim(strip_tags((string) $m->full_description));
        }
        if (mb_strlen($desc) > 5000) {
            $desc = mb_substr($desc, 0, 4997).'…';
        }

        $product = [
            '@type' => 'Product',
            'name' => $name,
            'url' => $canonicalUrl,
        ];
        if ($desc !== '') {
            $product['description'] = $desc;
        }
        if (TenantSeoMerge::isFilled($m->cover_url)) {
            $product['image'] = [(string) $m->cover_url];
        }

        $price = (int) ($m->price_per_day ?? 0);
        if ($price > 0) {
            $product['offers'] = [
                '@type' => 'Offer',
                'priceCurrency' => 'RUB',
                'price' => (string) $price,
                'url' => $canonicalUrl,
            ];
        }

        return $product;
    }

    /**
     * @param  list<array{url: string, name: string}>  $entries
     * @return array<string, mixed>
     */
    private function itemList(array $entries): array
    {
        $elements = [];
        $pos = 1;
        foreach ($entries as $e) {
            $url = $e['url'] ?? '';
            $name = $e['name'] ?? '';
            if ($url === '' || $name === '') {
                continue;
            }
            $elements[] = [
                '@type' => 'ListItem',
                'position' => $pos++,
                'item' => [
                    '@type' => 'Product',
                    'name' => $name,
                    'url' => $url,
                ],
            ];
        }

        return [
            '@type' => 'ItemList',
            'itemListElement' => $elements,
        ];
    }

    /**
     * @return list<array{url: string, name: string}>
     */
    public function catalogItemEntries(Tenant $tenant, Collection $motorcycles): array
    {
        $base = rtrim($this->canonicalBase->resolve($tenant), '/');
        $out = [];
        foreach ($motorcycles as $m) {
            if (! $m instanceof Motorcycle) {
                continue;
            }
            $slug = trim((string) $m->slug);
            if ($slug === '') {
                continue;
            }
            $out[] = [
                'url' => $base.'/moto/'.rawurlencode($slug),
                'name' => trim((string) $m->name) ?: $slug,
            ];
        }

        return $out;
    }
}
