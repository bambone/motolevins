<?php

namespace App\Services\Seo;

use App\Models\Motorcycle;
use App\Models\Tenant;
use Illuminate\Http\Request;

/**
 * Filament / admin preview must use the same {@see TenantSeoResolver} path as the public site.
 */
final class TenantSeoPublicPreviewService
{
    public function __construct(
        private TenantCanonicalPublicBaseUrl $canonicalBase,
        private TenantSeoResolver $resolver,
    ) {}

    /**
     * @return array{title: string, description: string}
     */
    public function motorcycleSnippet(Tenant $tenant, Motorcycle $motorcycle): array
    {
        $motorcycle->loadMissing(['seoMeta', 'category']);
        $base = rtrim($this->canonicalBase->resolve($tenant), '/');
        $slug = trim((string) $motorcycle->slug);
        $uri = $slug === '' ? $base.'/' : $base.'/moto/'.rawurlencode($slug);
        $request = Request::create($uri, 'GET');

        $resolved = $this->resolver->resolve($request, $tenant, 'motorcycle.show', $motorcycle);

        return [
            'title' => $resolved->title,
            'description' => $resolved->description,
        ];
    }
}
