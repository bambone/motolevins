<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Tenant\Resources\SchedulingTargetResource\Pages\EditSchedulingTarget;
use App\Filament\Tenant\Support\SchedulingTargetResourceAttachmentSync;
use App\Models\SchedulingResource;
use App\Models\SchedulingTarget;
use App\Models\User;
use App\Scheduling\Enums\AssignmentStrategy;
use App\Scheduling\Enums\CalendarUsageMode;
use App\Scheduling\Enums\ExternalBusyEffect;
use App\Scheduling\Enums\OccupancyScopeMode;
use App\Scheduling\Enums\SchedulingResourceType;
use App\Scheduling\Enums\SchedulingScope;
use App\Scheduling\Enums\SchedulingTargetType;
use App\Scheduling\Enums\TentativeEventsPolicy;
use App\Scheduling\Enums\UnconfirmedRequestsPolicy;
use App\Scheduling\SchedulingTimezoneOptions;
use App\Services\CurrentTenantManager;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

final class SchedulingTargetResourcePivotTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_edit_preserves_existing_pivot_and_defaults_for_new_resource(): void
    {
        $tenantA = $this->createTenantWithActiveDomain('st_pivot_a');
        $tenantB = $this->createTenantWithActiveDomain('st_pivot_b');

        $rA1 = $this->makeSchedulingResource($tenantA->id, 'R A1');
        $rA2 = $this->makeSchedulingResource($tenantA->id, 'R A2');
        $this->makeSchedulingResource($tenantB->id, 'R B1');

        $target = SchedulingTarget::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenantA->id,
            'target_type' => SchedulingTargetType::Generic,
            'target_id' => 1,
            'label' => 'Pivot test target',
            'scheduling_enabled' => true,
            'external_busy_enabled' => false,
            'internal_busy_enabled' => true,
            'auto_write_to_calendar_enabled' => false,
            'occupancy_scope_mode' => OccupancyScopeMode::Generic,
            'calendar_usage_mode' => CalendarUsageMode::Disabled,
            'external_busy_effect' => ExternalBusyEffect::InformationalOnly,
            'stale_busy_policy' => null,
            'is_active' => true,
        ]);

        $target->schedulingResources()->attach($rA1->id, [
            'priority' => 7,
            'is_default' => true,
            'assignment_strategy' => AssignmentStrategy::RoundRobin->value,
        ]);

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenantA->id, ['role' => 'tenant_owner', 'status' => 'active']);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($user);
        app(CurrentTenantManager::class)->setTenant($tenantA);

        Livewire::test(EditSchedulingTarget::class, ['record' => $target->getKey()])
            ->fillForm([
                'schedulingResources' => [$rA1->id, $rA2->id],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $row1 = DB::table('scheduling_target_resource')
            ->where('scheduling_target_id', $target->id)
            ->where('scheduling_resource_id', $rA1->id)
            ->first();
        $this->assertNotNull($row1);
        $this->assertSame(7, (int) $row1->priority);
        $this->assertSame(1, (int) $row1->is_default);
        $this->assertSame(AssignmentStrategy::RoundRobin->value, $row1->assignment_strategy);

        $row2 = DB::table('scheduling_target_resource')
            ->where('scheduling_target_id', $target->id)
            ->where('scheduling_resource_id', $rA2->id)
            ->first();
        $this->assertNotNull($row2);
        $this->assertSame(0, (int) $row2->priority);
        $this->assertSame(0, (int) $row2->is_default);
        $this->assertSame('first_available', $row2->assignment_strategy);
    }

    public function test_edit_rejects_foreign_tenant_scheduling_resource_ids(): void
    {
        $tenantA = $this->createTenantWithActiveDomain('st_scope_a');
        $tenantB = $this->createTenantWithActiveDomain('st_scope_b');

        $rA1 = $this->makeSchedulingResource($tenantA->id, 'R scope A1');
        $rB1 = $this->makeSchedulingResource($tenantB->id, 'R scope B1');

        $target = SchedulingTarget::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenantA->id,
            'target_type' => SchedulingTargetType::Generic,
            'target_id' => 1,
            'label' => 'Scope test target',
            'scheduling_enabled' => true,
            'external_busy_enabled' => false,
            'internal_busy_enabled' => true,
            'auto_write_to_calendar_enabled' => false,
            'occupancy_scope_mode' => OccupancyScopeMode::Generic,
            'calendar_usage_mode' => CalendarUsageMode::Disabled,
            'external_busy_effect' => ExternalBusyEffect::InformationalOnly,
            'stale_busy_policy' => null,
            'is_active' => true,
        ]);
        $target->schedulingResources()->attach($rA1->id, SchedulingTargetResourceAttachmentSync::defaultPivotRow());

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenantA->id, ['role' => 'tenant_owner', 'status' => 'active']);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($user);
        app(CurrentTenantManager::class)->setTenant($tenantA);

        Livewire::test(EditSchedulingTarget::class, ['record' => $target->getKey()])
            ->fillForm([
                'schedulingResources' => [$rA1->id, $rB1->id],
            ])
            ->call('save');

        $attachedIds = DB::table('scheduling_target_resource')
            ->where('scheduling_target_id', $target->id)
            ->pluck('scheduling_resource_id')
            ->map(static fn ($id): int => (int) $id)
            ->sort()
            ->values()
            ->all();

        $this->assertSame([(int) $rA1->id], $attachedIds);
    }

    private function makeSchedulingResource(int $tenantId, string $label): SchedulingResource
    {
        return SchedulingResource::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenantId,
            'resource_type' => SchedulingResourceType::Person->value,
            'user_id' => null,
            'label' => $label,
            'timezone' => SchedulingTimezoneOptions::DEFAULT_IDENTIFIER,
            'tentative_events_policy' => TentativeEventsPolicy::ProviderDefault,
            'unconfirmed_requests_policy' => UnconfirmedRequestsPolicy::Ignore,
            'is_active' => true,
        ]);
    }
}
