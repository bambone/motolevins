<?php

namespace Tests\Feature\CRM;

use App\Filament\Platform\Resources\CrmRequestResource as PlatformCrmRequestResource;
use App\Filament\Tenant\Resources\CrmRequestResource as TenantCrmRequestResource;
use App\Models\CrmRequest;
use App\Tenant\CurrentTenant;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

class CrmRequestResourceScopeTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    public function test_platform_resource_query_returns_only_null_tenant_rows(): void
    {
        $tenantA = $this->createTenantWithActiveDomain('ta');
        $platform = $this->makeCrmRequest(null, ['email' => 'plat-scope@example.test']);
        $tenantRow = $this->makeCrmRequest($tenantA->id, ['email' => 'tenant-scope@example.test']);

        Filament::setCurrentPanel(Filament::getPanel('platform'));

        $ids = PlatformCrmRequestResource::getEloquentQuery()->pluck('id')->all();

        $this->assertContains($platform->id, $ids);
        $this->assertNotContains($tenantRow->id, $ids);

        Filament::setCurrentPanel(null);
    }

    public function test_tenant_resource_query_returns_only_current_tenant_rows(): void
    {
        $tenantA = $this->createTenantWithActiveDomain('ta');
        $tenantB = $this->createTenantWithActiveDomain('tb');

        $crmA = $this->makeCrmRequest($tenantA->id, ['email' => 'scope-a@example.test']);
        $crmB = $this->makeCrmRequest($tenantB->id, ['email' => 'scope-b@example.test']);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $domain = $tenantA->domains()->where('is_primary', true)->first();
        $this->app->instance(CurrentTenant::class, new CurrentTenant($tenantA, $domain, false, $this->tenancyHostForSlug('ta')));

        $ids = TenantCrmRequestResource::getEloquentQuery()->pluck('id')->all();

        $this->assertContains($crmA->id, $ids);
        $this->assertNotContains($crmB->id, $ids);

        Filament::setCurrentPanel(null);
    }

    public function test_tenant_resource_query_returns_empty_set_without_current_tenant(): void
    {
        $tenantA = $this->createTenantWithActiveDomain('ta');
        $this->makeCrmRequest($tenantA->id);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->app->instance(CurrentTenant::class, new CurrentTenant(null, null, false, $this->tenancyHostForSlug('ta')));

        $count = TenantCrmRequestResource::getEloquentQuery()->count();
        $this->assertSame(0, $count);
        $this->assertGreaterThan(0, CrmRequest::query()->count());

        Filament::setCurrentPanel(null);
    }
}
