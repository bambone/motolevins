<?php

namespace Tests\Feature\CRM;

use App\Livewire\Crm\CrmRequestWorkspace;
use App\Models\Tenant;
use App\Models\User;
use App\Tenant\CurrentTenant;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

class CrmRequestWorkspaceLivewireTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_platform_user_mounts_workspace_for_platform_scoped_crm_without_guard_authorize_error(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('platform'));

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('platform_owner');
        $crm = $this->makeCrmRequest(null, ['request_type' => 'platform_contact']);

        $html = Livewire::actingAs($user)
            ->test(CrmRequestWorkspace::class, ['crmRequestId' => $crm->id])
            ->assertOk()
            ->html();

        $this->assertStringContainsString('width="14"', $html, 'CRM workspace SVGs must set explicit width (Tailwind alone is unreliable in Filament modals).');
        $this->assertStringContainsString('height="14"', $html);
        $this->assertStringContainsString('crm-svg-icon-host', $html);

        Filament::setCurrentPanel(null);
    }

    public function test_platform_user_cannot_mount_workspace_for_tenant_scoped_crm(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('platform'));

        $tenant = $this->createTenantWithActiveDomain('tw');
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('platform_owner');
        $tenantCrm = $this->makeCrmRequest($tenant->id, ['request_type' => 'tenant_booking']);

        Livewire::actingAs($user)
            ->test(CrmRequestWorkspace::class, ['crmRequestId' => $tenantCrm->id])
            ->assertForbidden();

        Filament::setCurrentPanel(null);
    }

    public function test_tenant_operator_mounts_workspace_for_own_crm(): void
    {
        $tenantA = $this->createTenantWithActiveDomain('twa');
        $crmA = $this->makeCrmRequest($tenantA->id, ['request_type' => 'tenant_booking']);

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenantA->id, ['role' => 'operator', 'status' => 'active']);

        $this->bindTenantFilamentContext($tenantA);

        Livewire::actingAs($user)
            ->test(CrmRequestWorkspace::class, ['crmRequestId' => $crmA->id])
            ->assertOk();

        Filament::setCurrentPanel(null);
    }

    public function test_tenant_operator_cannot_mount_workspace_for_other_tenant_crm(): void
    {
        $tenantA = $this->createTenantWithActiveDomain('twb');
        $tenantB = $this->createTenantWithActiveDomain('twc');
        $crmB = $this->makeCrmRequest($tenantB->id, ['request_type' => 'tenant_booking']);

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenantA->id, ['role' => 'operator', 'status' => 'active']);

        $this->bindTenantFilamentContext($tenantA);

        Livewire::actingAs($user)
            ->test(CrmRequestWorkspace::class, ['crmRequestId' => $crmB->id])
            ->assertForbidden();

        Filament::setCurrentPanel(null);
    }

    public function test_tenant_operator_in_two_tenants_cannot_mount_workspace_for_tenant_b_crm_on_host_context_a(): void
    {
        $tenantA = $this->createTenantWithActiveDomain('twd');
        $tenantB = $this->createTenantWithActiveDomain('twe');
        $crmB = $this->makeCrmRequest($tenantB->id, ['request_type' => 'tenant_booking']);

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenantA->id, ['role' => 'operator', 'status' => 'active']);
        $user->tenants()->attach($tenantB->id, ['role' => 'operator', 'status' => 'active']);

        $this->bindTenantFilamentContext($tenantA);

        Livewire::actingAs($user)
            ->test(CrmRequestWorkspace::class, ['crmRequestId' => $crmB->id])
            ->assertForbidden();

        Filament::setCurrentPanel(null);
    }

    public function test_fleet_manager_cannot_mount_workspace_even_for_own_tenant_crm(): void
    {
        $tenantA = $this->createTenantWithActiveDomain('twf');
        $crmA = $this->makeCrmRequest($tenantA->id, ['request_type' => 'tenant_booking']);

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenantA->id, ['role' => 'fleet_manager', 'status' => 'active']);

        $this->bindTenantFilamentContext($tenantA);

        Livewire::actingAs($user)
            ->test(CrmRequestWorkspace::class, ['crmRequestId' => $crmA->id])
            ->assertForbidden();

        Filament::setCurrentPanel(null);
    }

    private function bindTenantFilamentContext(Tenant $tenant): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $domain = $tenant->domains()->where('is_primary', true)->first();
        $this->app->instance(
            CurrentTenant::class,
            new CurrentTenant($tenant, $domain, false, $this->tenancyHostForSlug((string) $tenant->slug))
        );
    }
}
