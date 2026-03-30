<?php

namespace Tests\Feature\Tenant\Seo;

use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Models\TenantSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TenantSitemapDisabledTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Cache::flush();
    }

    public function test_sitemap_returns_404_when_disabled(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Seo Off',
            'slug' => 'seooff',
            'status' => 'active',
        ]);

        TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'host' => 'seooff.apex.test',
            'type' => TenantDomain::TYPE_SUBDOMAIN,
            'is_primary' => true,
            'status' => TenantDomain::STATUS_ACTIVE,
            'ssl_status' => TenantDomain::SSL_NOT_REQUIRED,
            'verified_at' => now(),
            'activated_at' => now(),
        ]);

        TenantSetting::setForTenant($tenant->id, 'seo.sitemap_enabled', false, 'boolean');

        $this->call('GET', 'http://seooff.apex.test/sitemap.xml')
            ->assertNotFound();

        $this->call('GET', 'http://seooff.apex.test/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function test_sitemap_returns_xml_when_enabled(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Seo On',
            'slug' => 'seoon',
            'status' => 'active',
        ]);

        TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'host' => 'seoon.apex.test',
            'type' => TenantDomain::TYPE_SUBDOMAIN,
            'is_primary' => true,
            'status' => TenantDomain::STATUS_ACTIVE,
            'ssl_status' => TenantDomain::SSL_NOT_REQUIRED,
            'verified_at' => now(),
            'activated_at' => now(),
        ]);

        TenantSetting::setForTenant($tenant->id, 'seo.sitemap_enabled', true, 'boolean');
        TenantSetting::setForTenant($tenant->id, 'general.domain', 'https://seoon.apex.test', 'string');

        $this->call('GET', 'http://seoon.apex.test/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<urlset', false);
    }
}
