<?php

declare(strict_types=1);

namespace Tests\Unit\Scheduling;

use App\Models\CalendarConnection;
use App\Scheduling\Enums\CalendarAccessMode;
use App\Scheduling\Enums\CalendarProviderType;
use App\Scheduling\Enums\IntegrationErrorPolicy;
use App\Scheduling\Enums\SchedulingScope;
use App\Scheduling\SchedulingIntegrationGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

final class SchedulingIntegrationGateTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    public function test_no_errors_does_not_block_and_has_no_warnings(): void
    {
        $tenant = $this->createTenantWithActiveDomain('int_gate_ok');
        $tenant->update(['scheduling_integration_error_policy' => IntegrationErrorPolicy::BlockScheduling]);
        $gate = app(SchedulingIntegrationGate::class);

        $this->assertFalse($gate->blocksPublicAppointmentSlots($tenant));
        $this->assertSame([], $gate->warningCodesForTenant($tenant));
    }

    public function test_last_error_with_block_policy_blocks_slots(): void
    {
        $tenant = $this->createTenantWithActiveDomain('int_gate_block');
        $tenant->update(['scheduling_integration_error_policy' => IntegrationErrorPolicy::BlockScheduling]);
        CalendarConnection::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'provider' => CalendarProviderType::Google,
            'access_mode' => CalendarAccessMode::Oauth,
            'display_name' => 'E',
            'is_active' => true,
            'last_error' => 'failed',
        ]);

        $gate = app(SchedulingIntegrationGate::class);
        $this->assertTrue($gate->blocksPublicAppointmentSlots($tenant));
        $this->assertSame([], $gate->warningCodesForTenant($tenant));
    }

    public function test_last_error_with_warn_policy_emits_warning_code(): void
    {
        $tenant = $this->createTenantWithActiveDomain('int_gate_warn');
        $tenant->update(['scheduling_integration_error_policy' => IntegrationErrorPolicy::WarnOnly]);
        CalendarConnection::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'provider' => CalendarProviderType::Google,
            'access_mode' => CalendarAccessMode::Oauth,
            'display_name' => 'E',
            'is_active' => true,
            'last_error' => 'failed',
        ]);

        $gate = app(SchedulingIntegrationGate::class);
        $this->assertFalse($gate->blocksPublicAppointmentSlots($tenant));
        $this->assertSame(['scheduling_calendar_integration_error'], $gate->warningCodesForTenant($tenant));
    }

    public function test_inactive_connection_does_not_block(): void
    {
        $tenant = $this->createTenantWithActiveDomain('int_gate_inactive');
        $tenant->update(['scheduling_integration_error_policy' => IntegrationErrorPolicy::BlockScheduling]);
        CalendarConnection::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'provider' => CalendarProviderType::Google,
            'access_mode' => CalendarAccessMode::Oauth,
            'display_name' => 'E',
            'is_active' => false,
            'last_error' => 'failed',
        ]);

        $gate = app(SchedulingIntegrationGate::class);
        $this->assertFalse($gate->blocksPublicAppointmentSlots($tenant));
    }

    public function test_error_on_other_tenant_does_not_affect_current(): void
    {
        $a = $this->createTenantWithActiveDomain('int_gate_a');
        $b = $this->createTenantWithActiveDomain('int_gate_b');
        $a->update(['scheduling_integration_error_policy' => IntegrationErrorPolicy::BlockScheduling]);
        $b->update(['scheduling_integration_error_policy' => IntegrationErrorPolicy::BlockScheduling]);

        CalendarConnection::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $b->id,
            'provider' => CalendarProviderType::Google,
            'access_mode' => CalendarAccessMode::Oauth,
            'display_name' => 'E',
            'is_active' => true,
            'last_error' => 'failed',
        ]);

        $gate = app(SchedulingIntegrationGate::class);
        $this->assertFalse($gate->blocksPublicAppointmentSlots($a));
        $this->assertTrue($gate->blocksPublicAppointmentSlots($b));
    }
}
