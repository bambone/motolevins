<?php

namespace Tests\Feature\CRM;

use App\Models\CrmRequest;
use App\Models\User;
use App\Tenant\CurrentTenant;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

class CrmRequestPolicyTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_platform_owner_can_view_platform_scoped_crm_only(): void
    {
        $tenantB = $this->createTenantWithActiveDomain('tb');
        $platformCrm = $this->makeCrmRequest(null, ['request_type' => 'platform_contact']);
        $tenantCrm = $this->makeCrmRequest($tenantB->id, ['request_type' => 'tenant_booking']);

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('platform_owner');

        $this->assertTrue(Gate::forUser($user)->allows('viewAny', CrmRequest::class));
        $this->assertTrue(Gate::forUser($user)->allows('view', $platformCrm));
        $this->assertFalse(Gate::forUser($user)->allows('view', $tenantCrm));
    }

    public function test_tenant_user_with_manage_leads_can_view_own_tenant_crm_only(): void
    {
        $tenantA = $this->createTenantWithActiveDomain('ta');
        $tenantB = $this->createTenantWithActiveDomain('tb');

        $crmA = $this->makeCrmRequest($tenantA->id, ['email' => 'a-own@example.test']);
        $crmB = $this->makeCrmRequest($tenantB->id, ['email' => 'b-other@example.test']);
        $platformCrm = $this->makeCrmRequest(null, ['email' => 'platform@example.test']);

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenantA->id, ['role' => 'operator', 'status' => 'active']);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $domain = $tenantA->domains()->where('is_primary', true)->first();
        $this->app->instance(CurrentTenant::class, new CurrentTenant($tenantA, $domain, false, $this->tenancyHostForSlug('ta')));

        $this->assertTrue(Gate::forUser($user)->allows('viewAny', CrmRequest::class));
        $this->assertTrue(Gate::forUser($user)->allows('view', $crmA));
        $this->assertFalse(Gate::forUser($user)->allows('view', $crmB));
        $this->assertFalse(Gate::forUser($user)->allows('view', $platformCrm));

        Filament::setCurrentPanel(null);
    }

    public function test_fleet_manager_cannot_view_crm_records(): void
    {
        $tenantA = $this->createTenantWithActiveDomain('ta');
        $crmA = $this->makeCrmRequest($tenantA->id);

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenantA->id, ['role' => 'fleet_manager', 'status' => 'active']);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $domain = $tenantA->domains()->where('is_primary', true)->first();
        $this->app->instance(CurrentTenant::class, new CurrentTenant($tenantA, $domain, false, $this->tenancyHostForSlug('ta')));

        $this->assertFalse(Gate::forUser($user)->allows('viewAny', CrmRequest::class));
        $this->assertFalse(Gate::forUser($user)->allows('view', $crmA));

        Filament::setCurrentPanel(null);
    }

    public function test_tenant_user_in_both_tenants_cannot_view_crm_of_tenant_b_while_host_context_is_a(): void
    {
        $tenantA = $this->createTenantWithActiveDomain('ta');
        $tenantB = $this->createTenantWithActiveDomain('tb');

        $crmB = $this->makeCrmRequest($tenantB->id, ['email' => 'both-b@example.test']);

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenantA->id, ['role' => 'operator', 'status' => 'active']);
        $user->tenants()->attach($tenantB->id, ['role' => 'operator', 'status' => 'active']);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $domainA = $tenantA->domains()->where('is_primary', true)->first();
        $this->app->instance(CurrentTenant::class, new CurrentTenant($tenantA, $domainA, false, $this->tenancyHostForSlug('ta')));

        $this->assertFalse(Gate::forUser($user)->allows('view', $crmB));

        Filament::setCurrentPanel(null);
    }

    public function test_tenant_view_any_denied_when_membership_not_in_current_tenant(): void
    {
        $tenantA = $this->createTenantWithActiveDomain('ta');
        $tenantB = $this->createTenantWithActiveDomain('tb');

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenantB->id, ['role' => 'operator', 'status' => 'active']);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $domainA = $tenantA->domains()->where('is_primary', true)->first();
        $this->app->instance(CurrentTenant::class, new CurrentTenant($tenantA, $domainA, false, $this->tenancyHostForSlug('ta')));

        $this->assertFalse(Gate::forUser($user)->allows('viewAny', CrmRequest::class));

        Filament::setCurrentPanel(null);
    }

    public function test_tenant_view_any_denied_without_resolved_current_tenant(): void
    {
        $tenantA = $this->createTenantWithActiveDomain('ta');

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenantA->id, ['role' => 'operator', 'status' => 'active']);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->app->instance(CurrentTenant::class, new CurrentTenant(null, null, false, $this->tenancyHostForSlug('ta')));

        $this->assertFalse(Gate::forUser($user)->allows('viewAny', CrmRequest::class));

        Filament::setCurrentPanel(null);
    }
}
