<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Tenant\TenantResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Relies on phpunit.xml env: TENANCY_CENTRAL_DOMAINS, PLATFORM_HOST, TENANCY_ROOT_DOMAIN (apex.test host set).
 */
class HostRoutingSplitTest extends TestCase
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

    public function test_central_domain_root_returns_marketing_landing(): void
    {
        $this->getWithHost('apex.test', '/')
            ->assertOk()
            ->assertSee('Платформа для аренды', false);
    }

    public function test_platform_host_root_redirects_to_platform_panel(): void
    {
        $this->getWithHost('platform.apex.test', '/')
            ->assertRedirect('http://platform.apex.test/platform');
    }

    public function test_platform_host_resolver_is_non_tenant_without_tenant(): void
    {
        $current = app(TenantResolver::class)->resolve('platform.apex.test');

        $this->assertTrue($current->isNonTenantHost);
        $this->assertNull($current->tenant);
    }

    public function test_tenant_subdomain_root_is_tenant_public_not_marketing(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Split Pub',
            'slug' => 'splitpub',
            'status' => 'active',
        ]);

        TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'host' => 'splitpub.apex.test',
            'type' => TenantDomain::TYPE_SUBDOMAIN,
            'is_primary' => true,
            'status' => TenantDomain::STATUS_ACTIVE,
            'ssl_status' => TenantDomain::SSL_NOT_REQUIRED,
            'verified_at' => now(),
            'activated_at' => now(),
        ]);

        Cache::flush();

        $this->getWithHost('splitpub.apex.test', '/')
            ->assertOk()
            ->assertDontSee('Маркетинговый сайт платформы', false);
    }

    public function test_tenant_subdomain_admin_login_is_not_404(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Split Adm',
            'slug' => 'splitadm',
            'status' => 'active',
        ]);

        TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'host' => 'splitadm.apex.test',
            'type' => TenantDomain::TYPE_SUBDOMAIN,
            'is_primary' => true,
            'status' => TenantDomain::STATUS_ACTIVE,
            'ssl_status' => TenantDomain::SSL_NOT_REQUIRED,
            'verified_at' => now(),
            'activated_at' => now(),
        ]);

        Cache::flush();

        $response = $this->getWithHost('splitadm.apex.test', '/admin/login');
        $this->assertNotSame(404, $response->getStatusCode(), 'tenant admin login should not be 404');
    }

    public function test_central_domain_admin_login_returns_404(): void
    {
        $this->getWithHost('apex.test', '/admin/login')->assertNotFound();
    }

    public function test_platform_host_admin_login_is_not_tenant_admin_flow(): void
    {
        $this->getWithHost('platform.apex.test', '/admin/login')->assertNotFound();
    }
}
