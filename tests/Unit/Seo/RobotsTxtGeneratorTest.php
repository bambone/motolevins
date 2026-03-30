<?php

namespace Tests\Unit\Seo;

use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Services\Seo\RobotsTxtGenerator;
use App\Services\Seo\TenantCanonicalPublicBaseUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RobotsTxtGeneratorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_indexing_disabled_outputs_disallow_all(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Robots T',
            'slug' => 'robots-t',
            'status' => 'active',
        ]);
        TenantSetting::setForTenant($tenant->id, 'seo.indexing_enabled', false, 'boolean');

        $gen = new RobotsTxtGenerator(new TenantCanonicalPublicBaseUrl);
        $out = $gen->generate($tenant);

        $this->assertStringContainsString('Disallow: /', $out);
        $this->assertStringContainsString('User-agent: *', $out);
    }

    public function test_template_includes_sitemap_when_enabled(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Robots T2',
            'slug' => 'robots-t2',
            'status' => 'active',
        ]);
        TenantSetting::setForTenant($tenant->id, 'general.domain', 'https://example.test', 'string');
        TenantSetting::setForTenant($tenant->id, 'seo.indexing_enabled', true, 'boolean');
        TenantSetting::setForTenant($tenant->id, 'seo.sitemap_enabled', true, 'boolean');
        TenantSetting::setForTenant($tenant->id, 'seo.robots_include_sitemap', true, 'boolean');

        $gen = new RobotsTxtGenerator(new TenantCanonicalPublicBaseUrl);
        $out = $gen->generate($tenant);

        $this->assertStringContainsString('Sitemap: https://example.test/sitemap.xml', $out);
    }

    public function test_template_omits_sitemap_when_sitemap_disabled(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Robots T3',
            'slug' => 'robots-t3',
            'status' => 'active',
        ]);
        TenantSetting::setForTenant($tenant->id, 'general.domain', 'https://example.test', 'string');
        TenantSetting::setForTenant($tenant->id, 'seo.indexing_enabled', true, 'boolean');
        TenantSetting::setForTenant($tenant->id, 'seo.sitemap_enabled', false, 'boolean');
        TenantSetting::setForTenant($tenant->id, 'seo.robots_include_sitemap', true, 'boolean');

        $gen = new RobotsTxtGenerator(new TenantCanonicalPublicBaseUrl);
        $out = $gen->generate($tenant);

        $this->assertStringNotContainsString('Sitemap:', $out);
    }
}
