<?php

namespace Tests\Feature\TenantPush;

use App\TenantPush\TenantPushFeatureGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

class TenantPushPublicRoutesTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    protected function getWithHost(string $host, string $path = '/'): TestResponse
    {
        $path = str_starts_with($path, '/') ? $path : '/'.$path;
        $url = 'http://'.$host.$path;

        return $this->call('GET', $url);
    }

    public function test_manifest_and_onesignal_worker_return_200_on_tenant_host(): void
    {
        config(['tenancy.central_domains' => ['localhost', '127.0.0.1']]);
        config(['tenancy.root_domain' => 'test']);

        $tenant = $this->createTenantWithActiveDomain('pwaroutes');
        $settings = app(TenantPushFeatureGate::class)->ensureSettings($tenant);
        $settings->is_pwa_enabled = true;
        $settings->save();

        $host = $this->tenancyHostForSlug('pwaroutes');

        $manifest = $this->getWithHost($host, '/manifest.webmanifest');
        $manifest->assertOk();
        $manifest->assertHeader('Content-Type', 'application/manifest+json');

        $worker = $this->getWithHost($host, '/push/onesignal/OneSignalSDKWorker.js');
        $worker->assertOk();
        $worker->assertSee('importScripts', false);
    }

    public function test_manifest_returns_tenant_brand_when_dynamic_pwa_toggle_disabled(): void
    {
        config(['tenancy.central_domains' => ['localhost', '127.0.0.1']]);
        config(['tenancy.root_domain' => 'test']);

        $tenant = $this->createTenantWithActiveDomain('pwanameoff', [
            'brand_name' => 'Сайт второго клиента',
        ]);

        $settings = app(TenantPushFeatureGate::class)->ensureSettings($tenant);
        $settings->is_pwa_enabled = false;
        $settings->save();

        $host = $this->tenancyHostForSlug('pwanameoff');

        $manifest = $this->getWithHost($host, '/manifest.webmanifest');
        $manifest->assertOk();
        $manifest->assertHeader('Content-Type', 'application/manifest+json');
        $manifest->assertJsonPath('name', 'Сайт второго клиента');
        $manifest->assertJsonPath('short_name', mb_substr('Сайт второго клиента', 0, 12));
    }

    public function test_manifest_theme_color_matches_public_layout_meta_theme_color(): void
    {
        config(['tenancy.central_domains' => ['localhost', '127.0.0.1']]);
        config(['tenancy.root_domain' => 'test']);

        $tenant = $this->createTenantWithActiveDomain('pwathemecolor');
        $settings = app(TenantPushFeatureGate::class)->ensureSettings($tenant);
        $settings->pwa_theme_color = '#cafe42';
        $settings->save();

        $host = $this->tenancyHostForSlug('pwathemecolor');

        $manifest = $this->getWithHost($host, '/manifest.webmanifest');
        $manifest->assertOk();
        $manifest->assertJsonPath('theme_color', '#cafe42');

        $home = $this->getWithHost($host, '/');
        $home->assertOk();
        $this->assertStringContainsString(
            '<meta name="theme-color" content="#cafe42">',
            $home->getContent(),
            'Public layout meta theme-color must match manifest when pwa_theme_color is set.'
        );
    }
}
