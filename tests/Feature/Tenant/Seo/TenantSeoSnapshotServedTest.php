<?php

namespace Tests\Feature\Tenant\Seo;

use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Models\TenantSeoFileGeneration;
use App\Models\TenantSetting;
use App\Services\Seo\TenantSeoFilePublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TenantSeoSnapshotServedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Cache::flush();
    }

    public function test_public_robots_prefers_snapshot_over_settings_drift(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Snap R',
            'slug' => 'snapr',
            'status' => 'active',
        ]);

        TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'host' => 'snapr.apex.test',
            'type' => TenantDomain::TYPE_SUBDOMAIN,
            'is_primary' => true,
            'status' => TenantDomain::STATUS_ACTIVE,
            'ssl_status' => TenantDomain::SSL_NOT_REQUIRED,
            'verified_at' => now(),
            'activated_at' => now(),
        ]);

        TenantSetting::setForTenant($tenant->id, 'general.domain', 'https://snapr.apex.test', 'string');
        TenantSetting::setForTenant($tenant->id, 'seo.indexing_enabled', true, 'boolean');
        TenantSetting::setForTenant($tenant->id, 'seo.sitemap_enabled', true, 'boolean');

        app(TenantSeoFilePublisher::class)->publishRobots(
            $tenant,
            null,
            TenantSeoFileGeneration::SOURCE_MANUAL,
            false,
            false,
        );

        TenantSetting::setForTenant($tenant->id, 'seo.indexing_enabled', false, 'boolean');
        Cache::flush();

        $body = $this->call('GET', 'http://snapr.apex.test/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->getContent();

        $this->assertStringContainsString('Allow: /', $body);
    }
}
