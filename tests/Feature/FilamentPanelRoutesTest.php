<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Smoke: Filament login routes respond without 5xx on correct hosts.
 */
class FilamentPanelRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    protected function getWithHost(string $host, string $path = '/'): TestResponse
    {
        $path = str_starts_with($path, '/') ? $path : '/'.$path;

        return $this->call('GET', 'http://'.$host.$path);
    }

    public function test_platform_panel_login_returns_ok(): void
    {
        $this->getWithHost('platform.apex.test', '/login')
            ->assertOk();
    }

    public function test_tenant_panel_login_returns_ok_on_tenant_host(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Panel T',
            'slug' => 'panelt',
            'status' => 'active',
        ]);
        TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'host' => 'panelt.apex.test',
            'type' => TenantDomain::TYPE_SUBDOMAIN,
            'is_primary' => true,
            'status' => TenantDomain::STATUS_ACTIVE,
            'ssl_status' => TenantDomain::SSL_NOT_REQUIRED,
            'verified_at' => now(),
            'activated_at' => now(),
        ]);
        Cache::flush();

        $this->getWithHost('panelt.apex.test', '/admin/login')
            ->assertOk();
    }
}
