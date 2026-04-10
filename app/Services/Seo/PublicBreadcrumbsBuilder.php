<?php

namespace App\Services\Seo;

use App\Models\LocationLandingPage;
use App\Models\Motorcycle;
use App\Models\Page;
use App\Models\SeoLandingPage;
use App\Models\Tenant;

/**
 * Single source for public UI breadcrumbs and JSON-LD BreadcrumbList items.
 *
 * @phpstan-type Crumb array{name: string, url: string}
 */
final class PublicBreadcrumbsBuilder
{
    public function __construct(
        private TenantCanonicalPublicBaseUrl $canonicalBase,
    ) {}

    /**
     * @return list<Crumb>
     */
    public function forHome(Tenant $tenant): array
    {
        $base = rtrim($this->canonicalBase->resolve($tenant), '/');

        return [
            ['name' => 'Главная', 'url' => $base.'/'],
        ];
    }

    /**
     * @return list<Crumb>
     */
    public function forMotorcyclesIndex(Tenant $tenant): array
    {
        $base = rtrim($this->canonicalBase->resolve($tenant), '/');

        return [
            ['name' => 'Главная', 'url' => $base.'/'],
            ['name' => 'Каталог', 'url' => $base.'/motorcycles'],
        ];
    }

    /**
     * @return list<Crumb>
     */
    public function forMotorcycle(Tenant $tenant, Motorcycle $m): array
    {
        $base = rtrim($this->canonicalBase->resolve($tenant), '/');
        $slug = trim((string) $m->slug);
        $name = trim((string) $m->name) ?: $slug;

        return [
            ['name' => 'Главная', 'url' => $base.'/'],
            ['name' => 'Каталог', 'url' => $base.'/motorcycles'],
            ['name' => $name, 'url' => $base.'/moto/'.rawurlencode($slug)],
        ];
    }

    /**
     * @return list<Crumb>
     */
    public function forCmsPage(Tenant $tenant, Page $page): array
    {
        $base = rtrim($this->canonicalBase->resolve($tenant), '/');
        $slug = ltrim((string) $page->slug, '/');
        $name = trim((string) $page->name) ?: $slug;

        return [
            ['name' => 'Главная', 'url' => $base.'/'],
            ['name' => $name, 'url' => $base.'/'.rawurlencode($slug)],
        ];
    }

    /**
     * @return list<Crumb>
     */
    public function forLocationLanding(Tenant $tenant, LocationLandingPage $page): array
    {
        $base = rtrim($this->canonicalBase->resolve($tenant), '/');
        $slug = trim((string) $page->slug);
        $name = trim((string) $page->title) ?: $slug;

        return [
            ['name' => 'Главная', 'url' => $base.'/'],
            ['name' => $name, 'url' => $base.'/locations/'.rawurlencode($slug)],
        ];
    }

    /**
     * @return list<Crumb>
     */
    public function forSeoLanding(Tenant $tenant, SeoLandingPage $page): array
    {
        $base = rtrim($this->canonicalBase->resolve($tenant), '/');
        $slug = trim((string) $page->slug);
        $name = trim((string) $page->title) ?: $slug;

        return [
            ['name' => 'Главная', 'url' => $base.'/'],
            ['name' => $name, 'url' => $base.'/landings/'.rawurlencode($slug)],
        ];
    }
}
