<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\CalendarConnection;
use App\Models\CalendarOccupancyMapping;
use App\Models\CalendarSubscription;
use App\Models\User;
use App\Scheduling\Enums\CalendarAccessMode;
use App\Scheduling\Enums\CalendarProviderType;
use App\Scheduling\Enums\OccupancyMappingType;
use App\Scheduling\Enums\SchedulingScope;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\Support\SchedulingTestScenarios;
use Tests\TestCase;

/**
 * Отдельно от {@see TenantSchedulingFilamentAccessTest}: scheduling включён, календарные интеграции выключены —
 * баннер в BODY_START и стабильные calendar-экраны без 5xx.
 */
final class TenantSchedulingCalendarIntegrationsGatingTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;
    use SchedulingTestScenarios;

    private const BANNER_SNIPPET = 'Календарные интеграции выключены';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    private function getWithHost(string $host, string $path): TestResponse
    {
        $path = str_starts_with($path, '/') ? $path : '/'.$path;

        return $this->call('GET', 'http://'.$host.$path);
    }

    public function test_gating_banner_visible_when_scheduling_on_and_calendar_integrations_off(): void
    {
        $tenant = $this->createTenantWithActiveDomain('cal_gate_banner', [
            'scheduling_module_enabled' => true,
            'calendar_integrations_enabled' => false,
        ]);

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'tenant_owner', 'status' => 'active']);

        $host = $this->tenancyHostForSlug('cal_gate_banner');

        foreach ([
            '/admin/bookable-services',
            '/admin/calendar-connections',
            '/admin/scheduling/slot-debug',
        ] as $path) {
            $r = $this->actingAs($user)->getWithHost($host, $path);
            $this->assertNotSame(500, $r->getStatusCode(), 'Unexpected 500 for '.$path);
            $r->assertOk();
            $r->assertSee(self::BANNER_SNIPPET, false);
        }
    }

    public function test_gating_banner_hidden_on_non_scheduling_admin_pages(): void
    {
        $tenant = $this->createTenantWithActiveDomain('cal_gate_hide', [
            'scheduling_module_enabled' => true,
            'calendar_integrations_enabled' => false,
        ]);

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'tenant_owner', 'status' => 'active']);

        $host = $this->tenancyHostForSlug('cal_gate_hide');

        $r = $this->actingAs($user)->getWithHost($host, '/admin');
        $this->assertNotSame(500, $r->getStatusCode());
        $r->assertOk();
        $r->assertDontSee(self::BANNER_SNIPPET, false);
    }

    public function test_calendar_connection_list_create_and_edit_survive_integrations_disabled(): void
    {
        $tenant = $this->createTenantWithActiveDomain('cal_gate_crud', [
            'scheduling_module_enabled' => true,
            'calendar_integrations_enabled' => false,
        ]);

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'tenant_owner', 'status' => 'active']);

        $host = $this->tenancyHostForSlug('cal_gate_crud');

        $list = $this->actingAs($user)->getWithHost($host, '/admin/calendar-connections');
        $this->assertNotSame(500, $list->getStatusCode());
        $list->assertOk();

        $create = $this->actingAs($user)->getWithHost($host, '/admin/calendar-connections/create');
        $this->assertNotSame(500, $create->getStatusCode());
        $create->assertOk();

        $conn = CalendarConnection::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'provider' => CalendarProviderType::Google,
            'access_mode' => CalendarAccessMode::Oauth,
            'display_name' => 'G-'.uniqid(),
            'is_active' => true,
        ]);

        $edit = $this->actingAs($user)->getWithHost($host, '/admin/calendar-connections/'.$conn->id.'/edit');
        $this->assertNotSame(500, $edit->getStatusCode());
        $edit->assertOk();
    }

    public function test_calendar_occupancy_mapping_list_and_edit_survive_integrations_disabled(): void
    {
        $tenant = $this->createTenantWithActiveDomain('cal_gate_map', [
            'scheduling_module_enabled' => true,
            'calendar_integrations_enabled' => false,
        ]);

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'tenant_owner', 'status' => 'active']);

        $host = $this->tenancyHostForSlug('cal_gate_map');

        $svc = $this->schedulingCreateBookableService($tenant);
        $target = $svc->schedulingTarget;
        $this->assertNotNull($target);

        $conn = CalendarConnection::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'provider' => CalendarProviderType::Google,
            'access_mode' => CalendarAccessMode::Oauth,
            'display_name' => 'Conn',
            'is_active' => true,
        ]);
        $sub = CalendarSubscription::query()->create([
            'calendar_connection_id' => $conn->id,
            'external_calendar_id' => 'primary',
            'title' => 'T-'.uniqid(),
            'use_for_busy' => true,
            'use_for_write' => false,
            'is_active' => true,
        ]);
        $mapping = CalendarOccupancyMapping::query()->create([
            'calendar_subscription_id' => $sub->id,
            'mapping_type' => OccupancyMappingType::CalendarToTarget,
            'scheduling_target_id' => $target->id,
            'is_active' => true,
        ]);

        $index = $this->actingAs($user)->getWithHost($host, '/admin/calendar-occupancy-mappings');
        $this->assertNotSame(500, $index->getStatusCode());
        $index->assertOk();

        $edit = $this->actingAs($user)->getWithHost($host, '/admin/calendar-occupancy-mappings/'.$mapping->id.'/edit');
        $this->assertNotSame(500, $edit->getStatusCode());
        $edit->assertOk();
    }
}
