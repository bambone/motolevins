<?php

namespace App\Services\Seo;

use App\Models\SeoMeta;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

final class JsonLdGenerator
{
    public function __construct(
        private TenantSeoJsonLdFactory $jsonLdFactory,
        private TenantSeoJsonLdOverrideMerger $overrideMerger,
    ) {}

    /**
     * @param  list<array{url: string, name: string}>|null  $itemListEntries
     * @param  list<array{name: string, url: string}>  $breadcrumbs
     * @return list<array<string, mixed>>
     */
    public function buildGraph(
        Tenant $tenant,
        string $routeName,
        ?Model $model,
        ?SeoMeta $seo,
        string $canonicalUrl,
        ?array $itemListEntries = null,
        array $breadcrumbs = [],
    ): array {
        $graph = $this->jsonLdFactory->buildBaseGraph(
            $tenant,
            $routeName,
            $model,
            $canonicalUrl,
            $itemListEntries,
        );

        $graph = $this->mergeBreadcrumbListIfMissing($graph, $breadcrumbs);

        return $this->overrideMerger->merge($graph, $seo);
    }

    /**
     * @param  list<array{name: string, url: string}>  $breadcrumbs
     * @param  list<array<string, mixed>>  $graph
     * @return list<array<string, mixed>>
     */
    private function mergeBreadcrumbListIfMissing(array $graph, array $breadcrumbs): array
    {
        if ($breadcrumbs === []) {
            return $graph;
        }
        foreach ($graph as $node) {
            if (isset($node['@type']) && $node['@type'] === 'BreadcrumbList') {
                return $graph;
            }
        }

        $bc = $this->jsonLdFactory->breadcrumbSchemaFromCrumbs($breadcrumbs);
        if ($bc === null) {
            return $graph;
        }
        $graph[] = $bc;

        return $graph;
    }

    /**
     * @return list<array{url: string, name: string}>
     */
    public function catalogItemEntries(Tenant $tenant, Collection $motorcycles): array
    {
        return $this->jsonLdFactory->catalogItemEntries($tenant, $motorcycles);
    }
}
