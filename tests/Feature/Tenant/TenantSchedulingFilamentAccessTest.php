<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\CalendarConnection;
use App\Models\CalendarOccupancyMapping;
use App\Models\CalendarSubscription;
use App\Models\Tenant;
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
 * Filament tenant panel: scheduling resources scoped to current tenant; smoke on key URLs.
 *
 * @see \Tests\Feature\Scheduling\COVERAGE_NOTES.md for concurrency and preview limits.
 */
final class TenantSchedulingFilamentAccessTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;
    use SchedulingTestScenarios;

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

    private function ownerOnTenant(User $user, Tenant $tenant): void
    {
        $user->tenants()->attach($tenant->id, ['role' => 'tenant_owner', 'status' => 'active']);
    }

    public function test_scheduling_targets_list_shows_only_current_tenant_labels(): void
    {
        $a = $this->createTenantWithActiveDomain('fil_tgt_a');
        $b = $this->createTenantWithActiveDomain('fil_tgt_b');

        $labelA = 'TARGET-A-'.uniqid();
        $labelB = 'TARGET-B-'.uniqid();

        $svcA = $this->schedulingCreateBookableService($a);
        $svcA->schedulingTarget?->update(['label' => $labelA, 'scheduling_enabled' => true]);

        $svcB = $this->schedulingCreateBookableService($b);
        $svcB->schedulingTarget?->update(['label' => $labelB, 'scheduling_enabled' => true]);

        $user = User::factory()->create(['status' => 'active']);
        $this->ownerOnTenant($user, $a);

        $response = $this->actingAs($user)->getWithHost($this->tenancyHostForSlug('fil_tgt_a'), '/admin/scheduling-targets');

        $response->assertOk();
        $response->assertSee($labelA, false);
        $response->assertDontSee($labelB, false);
    }

    public function test_scheduling_target_edit_for_other_tenant_returns_not_found(): void
    {
        $a = $this->createTenantWithActiveDomain('fil_edit_a');
        $b = $this->createTenantWithActiveDomain('fil_edit_b');

        $svcB = $this->schedulingCreateBookableService($b);
        $targetB = $svcB->schedulingTarget;
        $this->assertNotNull($targetB);

        $user = User::factory()->create(['status' => 'active']);
        $this->ownerOnTenant($user, $a);

        $response = $this->actingAs($user)->getWithHost(
            $this->tenancyHostForSlug('fil_edit_a'),
            '/admin/scheduling-targets/'.$targetB->id.'/edit',
        );

        $response->assertNotFound();
    }

    public function test_calendar_connections_list_scoped_by_tenant(): void
    {
        $a = $this->createTenantWithActiveDomain('fil_cal_a');
        $b = $this->createTenantWithActiveDomain('fil_cal_b');

        $nameA = 'CONN-A-'.uniqid();
        $nameB = 'CONN-B-'.uniqid();

        CalendarConnection::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $a->id,
            'provider' => CalendarProviderType::Google,
            'access_mode' => CalendarAccessMode::Oauth,
            'display_name' => $nameA,
            'is_active' => true,
        ]);
        CalendarConnection::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $b->id,
            'provider' => CalendarProviderType::Google,
            'access_mode' => CalendarAccessMode::Oauth,
            'display_name' => $nameB,
            'is_active' => true,
        ]);

        $user = User::factory()->create(['status' => 'active']);
        $this->ownerOnTenant($user, $a);

        $response = $this->actingAs($user)->getWithHost($this->tenancyHostForSlug('fil_cal_a'), '/admin/calendar-connections');

        $response->assertOk();
        $response->assertSee($nameA, false);
        $response->assertDontSee($nameB, false);
    }

    public function test_calendar_occupancy_mappings_list_scoped_via_connection_tenant(): void
    {
        $a = $this->createTenantWithActiveDomain('fil_map_a');
        $b = $this->createTenantWithActiveDomain('fil_map_b');

        $connA = CalendarConnection::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $a->id,
            'provider' => CalendarProviderType::Google,
            'access_mode' => CalendarAccessMode::Oauth,
            'display_name' => 'CA',
            'is_active' => true,
        ]);
        $connB = CalendarConnection::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $b->id,
            'provider' => CalendarProviderType::Google,
            'access_mode' => CalendarAccessMode::Oauth,
            'display_name' => 'CB',
            'is_active' => true,
        ]);

        $titleA = 'MAP-SUB-A-'.uniqid();
        $titleB = 'MAP-SUB-B-'.uniqid();

        $subA = CalendarSubscription::query()->create([
            'calendar_connection_id' => $connA->id,
            'external_calendar_id' => 'p',
            'title' => $titleA,
            'use_for_busy' => true,
            'use_for_write' => false,
            'is_active' => true,
        ]);
        $subB = CalendarSubscription::query()->create([
            'calendar_connection_id' => $connB->id,
            'external_calendar_id' => 'p',
            'title' => $titleB,
            'use_for_busy' => true,
            'use_for_write' => false,
            'is_active' => true,
        ]);

        $svcMapA = $this->schedulingCreateBookableService($a);
        $svcMapB = $this->schedulingCreateBookableService($b);
        $tA = $svcMapA->schedulingTarget;
        $tB = $svcMapB->schedulingTarget;
        $this->assertNotNull($tA);
        $this->assertNotNull($tB);

        CalendarOccupancyMapping::query()->create([
            'calendar_subscription_id' => $subA->id,
            'mapping_type' => OccupancyMappingType::CalendarToTarget,
            'scheduling_target_id' => $tA->id,
            'is_active' => true,
        ]);
        CalendarOccupancyMapping::query()->create([
            'calendar_subscription_id' => $subB->id,
            'mapping_type' => OccupancyMappingType::CalendarToTarget,
            'scheduling_target_id' => $tB->id,
            'is_active' => true,
        ]);

        $user = User::factory()->create(['status' => 'active']);
        $this->ownerOnTenant($user, $a);

        $response = $this->actingAs($user)->getWithHost($this->tenancyHostForSlug('fil_map_a'), '/admin/calendar-occupancy-mappings');

        $response->assertOk();
        $response->assertSee($titleA, false);
        $response->assertDontSee($titleB, false);
    }

    public function test_scheduling_debug_pages_return_ok_for_owner_when_module_enabled(): void
    {
        $tenant = $this->createTenantWithActiveDomain('fil_smoke_pages');
        $user = User::factory()->create(['status' => 'active']);
        $this->ownerOnTenant($user, $tenant);

        $host = $this->tenancyHostForSlug('fil_smoke_pages');

        $this->actingAs($user)->getWithHost($host, '/admin/scheduling/slot-debug')->assertOk();
        $this->actingAs($user)->getWithHost($host, '/admin/scheduling/occupancy-preview')->assertOk();
        $this->actingAs($user)->getWithHost($host, '/admin/scheduling/calendar-sync-health')->assertOk();
    }

    public function test_bookable_services_create_page_loads_without_500(): void
    {
        $tenant = $this->createTenantWithActiveDomain('fil_book_create');
        $user = User::factory()->create(['status' => 'active']);
        $this->ownerOnTenant($user, $tenant);

        $response = $this->actingAs($user)->getWithHost(
            $this->tenancyHostForSlug('fil_book_create'),
            '/admin/bookable-services/create',
        );

        $this->assertNotSame(500, $response->getStatusCode());
        $response->assertOk();
    }

    public function test_scheduling_module_disabled_blocks_bookable_services_area_without_server_error(): void
    {
        $tenant = $this->createTenantWithActiveDomain('fil_mod_off', ['scheduling_module_enabled' => false]);
        $user = User::factory()->create(['status' => 'active']);
        $this->ownerOnTenant($user, $tenant);

        $response = $this->actingAs($user)->getWithHost(
            $this->tenancyHostForSlug('fil_mod_off'),
            '/admin/bookable-services',
        );

        $this->assertNotSame(500, $response->getStatusCode());
        $this->assertFalse(
            $response->isOk(),
            'Bookable services index should not succeed when scheduling module is disabled for tenant.',
        );
    }
}
