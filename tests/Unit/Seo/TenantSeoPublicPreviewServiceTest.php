<?php

namespace Tests\Unit\Seo;

use App\Models\Category;
use App\Models\Motorcycle;
use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Models\TenantSetting;
use App\Services\Seo\TenantSeoPublicPreviewService;
use App\Services\Seo\TenantSeoResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class TenantSeoPublicPreviewServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_matches_resolver_output_for_motorcycle(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Preview T',
            'slug' => 'previewt',
            'status' => 'active',
        ]);

        TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'host' => 'preview.apex.test',
            'type' => TenantDomain::TYPE_SUBDOMAIN,
            'is_primary' => true,
            'status' => TenantDomain::STATUS_ACTIVE,
            'ssl_status' => TenantDomain::SSL_NOT_REQUIRED,
            'verified_at' => now(),
            'activated_at' => now(),
        ]);

        TenantSetting::setForTenant($tenant->id, 'general.domain', 'https://preview.apex.test', 'string');
        TenantSetting::setForTenant($tenant->id, 'general.site_name', 'Preview Rent', 'string');

        $cat = Category::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'C',
            'slug' => 'c',
        ]);

        $m = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'category_id' => $cat->id,
            'name' => 'Yamaha X',
            'slug' => 'yamaha-x',
            'status' => 'available',
            'show_in_catalog' => true,
        ]);

        $m->load(['seoMeta', 'category']);

        $preview = app(TenantSeoPublicPreviewService::class)->motorcycleSnippet($tenant, $m);

        $request = Request::create('https://preview.apex.test/moto/yamaha-x', 'GET');
        $resolved = app(TenantSeoResolver::class)->resolve($request, $tenant, 'motorcycle.show', $m);

        $this->assertSame($resolved->title, $preview['title']);
        $this->assertSame($resolved->description, $preview['description']);
    }
}
