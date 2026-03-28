<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Tenant\TenantResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class TenantResolutionTest extends TestCase
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
        $url = 'http://'.$host.$path;

        return $this->call('GET', $url);
    }

    public function test_active_subdomain_resolves_tenant_on_public_site(): void
    {
        config(['tenancy.central_domains' => ['localhost', '127.0.0.1']]);
        config(['tenancy.root_domain' => 'test']);

        $tenant = Tenant::query()->create([
            'name' => 'T',
            'slug' => 'acme',
            'status' => 'active',
        ]);

        TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'host' => 'acme.test',
            'type' => TenantDomain::TYPE_SUBDOMAIN,
            'is_primary' => true,
            'status' => TenantDomain::STATUS_ACTIVE,
            'ssl_status' => TenantDomain::SSL_NOT_REQUIRED,
            'verified_at' => now(),
            'activated_at' => now(),
        ]);

        $response = $this->getWithHost('acme.test');

        $response->assertOk();
        $this->assertSame($tenant->id, tenant()?->id);
    }

    public function test_pending_custom_domain_does_not_resolve(): void
    {
        config(['tenancy.central_domains' => ['localhost', '127.0.0.1']]);

        $tenant = Tenant::query()->create([
            'name' => 'T',
            'slug' => 'x',
            'status' => 'active',
        ]);

        TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'host' => 'custom.example',
            'type' => TenantDomain::TYPE_CUSTOM,
            'is_primary' => false,
            'status' => TenantDomain::STATUS_PENDING,
            'ssl_status' => TenantDomain::SSL_PENDING,
            'verification_token' => 'rb-test',
        ]);

        $response = $this->getWithHost('custom.example');

        $response->assertNotFound();
        $this->assertNull(tenant());
    }

    public function test_unknown_host_returns_domain_not_connected(): void
    {
        config(['tenancy.central_domains' => ['localhost', '127.0.0.1']]);

        $response = $this->getWithHost('unknown.invalid');

        $response->assertNotFound();
        $this->assertNull(tenant());
    }

    public function test_central_marketing_host_classifies_as_non_tenant(): void
    {
        config(['tenancy.central_domains' => ['marketing.test']]);
        config(['app.platform_host' => '']);

        $tenant = Tenant::query()->create([
            'name' => 'T',
            'slug' => 'solo',
            'status' => 'active',
        ]);

        TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'host' => 'solo.test',
            'type' => TenantDomain::TYPE_SUBDOMAIN,
            'is_primary' => true,
            'status' => TenantDomain::STATUS_ACTIVE,
            'ssl_status' => TenantDomain::SSL_NOT_REQUIRED,
            'verified_at' => now(),
            'activated_at' => now(),
        ]);

        Cache::flush();
        $current = app(TenantResolver::class)->resolve('marketing.test');

        $this->assertTrue($current->isNonTenantHost);
        $this->assertNull($current->tenant);
    }

    public function test_verifying_custom_domain_does_not_resolve(): void
    {
        config(['tenancy.central_domains' => ['localhost', '127.0.0.1']]);

        $tenant = Tenant::query()->create([
            'name' => 'T',
            'slug' => 'v',
            'status' => 'active',
        ]);

        TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'host' => 'verify.example',
            'type' => TenantDomain::TYPE_CUSTOM,
            'is_primary' => false,
            'status' => TenantDomain::STATUS_VERIFYING,
            'ssl_status' => TenantDomain::SSL_PENDING,
            'verification_token' => 'rb-v',
        ]);

        $response = $this->getWithHost('verify.example');

        $response->assertNotFound();
        $this->assertNull(tenant());
    }

    public function test_failed_custom_domain_does_not_resolve(): void
    {
        config(['tenancy.central_domains' => ['localhost', '127.0.0.1']]);

        $tenant = Tenant::query()->create([
            'name' => 'T',
            'slug' => 'f',
            'status' => 'active',
        ]);

        TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'host' => 'failed.example',
            'type' => TenantDomain::TYPE_CUSTOM,
            'is_primary' => false,
            'status' => TenantDomain::STATUS_FAILED,
            'ssl_status' => TenantDomain::SSL_FAILED,
            'verification_token' => 'rb-f',
        ]);

        $response = $this->getWithHost('failed.example');

        $response->assertNotFound();
        $this->assertNull(tenant());
    }

    public function test_active_custom_domain_resolves_same_tenant(): void
    {
        config(['tenancy.central_domains' => ['localhost', '127.0.0.1']]);
        config(['tenancy.root_domain' => 'test']);

        $tenant = Tenant::query()->create([
            'name' => 'T',
            'slug' => 'shop',
            'status' => 'active',
        ]);

        TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'host' => 'shop.test',
            'type' => TenantDomain::TYPE_SUBDOMAIN,
            'is_primary' => true,
            'status' => TenantDomain::STATUS_ACTIVE,
            'ssl_status' => TenantDomain::SSL_NOT_REQUIRED,
            'verified_at' => now(),
            'activated_at' => now(),
        ]);

        TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'host' => 'shop-brand.com',
            'type' => TenantDomain::TYPE_CUSTOM,
            'is_primary' => false,
            'status' => TenantDomain::STATUS_ACTIVE,
            'ssl_status' => TenantDomain::SSL_ISSUED,
            'verified_at' => now(),
            'activated_at' => now(),
        ]);

        $response = $this->getWithHost('shop-brand.com');

        $response->assertOk();
        $this->assertSame($tenant->id, tenant()?->id);
    }

    public function test_central_host_path_without_marketing_route_returns_not_found_not_tenant(): void
    {
        config(['tenancy.central_domains' => ['central.test']]);
        config(['app.platform_host' => '']);

        $tenant = Tenant::query()->create([
            'name' => 'T',
            'slug' => 'corp',
            'status' => 'active',
        ]);

        TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'host' => 'corp.test',
            'type' => TenantDomain::TYPE_SUBDOMAIN,
            'is_primary' => true,
            'status' => TenantDomain::STATUS_ACTIVE,
            'ssl_status' => TenantDomain::SSL_NOT_REQUIRED,
            'verified_at' => now(),
            'activated_at' => now(),
        ]);

        Cache::flush();
        $response = $this->getWithHost('central.test', '/articles');

        $response->assertNotFound();
        $this->assertNull(tenant());
    }
}
