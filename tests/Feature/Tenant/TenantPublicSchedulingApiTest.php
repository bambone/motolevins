<?php

namespace Tests\Feature\Tenant;

use App\Models\AvailabilityRule;
use App\Models\BookableService;
use App\Models\SchedulingResource;
use App\Scheduling\Enums\AssignmentStrategy;
use App\Scheduling\Enums\AvailabilityRuleType;
use App\Scheduling\Enums\SchedulingScope;
use App\Scheduling\Enums\SchedulingTargetType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

class TenantPublicSchedulingApiTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    public function test_slots_endpoint_returns_windows_minus_busy(): void
    {
        $tenant = $this->createTenantWithActiveDomain('schedapi');
        $host = $this->tenancyHostForSlug('schedapi');

        $service = BookableService::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'slug' => 'consult',
            'title' => 'Консультация',
            'duration_minutes' => 30,
            'slot_step_minutes' => 30,
            'buffer_before_minutes' => 0,
            'buffer_after_minutes' => 0,
            'min_booking_notice_minutes' => 0,
            'max_booking_horizon_days' => 30,
            'requires_confirmation' => false,
            'is_active' => true,
            'sort_weight' => 0,
        ]);

        $target = $service->schedulingTarget;
        $this->assertNotNull($target);
        $target->update([
            'scheduling_enabled' => true,
            'target_type' => SchedulingTargetType::BookableService,
            'target_id' => $service->id,
        ]);

        $resource = SchedulingResource::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'resource_type' => 'person',
            'label' => 'Мастер',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        $target->schedulingResources()->attach($resource->id, [
            'priority' => 0,
            'is_default' => true,
            'assignment_strategy' => AssignmentStrategy::FirstAvailable->value,
        ]);

        AvailabilityRule::query()->create([
            'scheduling_resource_id' => $resource->id,
            'applies_to_scheduling_target_id' => null,
            'applies_to_bookable_service_id' => null,
            'rule_type' => AvailabilityRuleType::WeeklyOpen,
            'weekday' => 1,
            'starts_at_local' => '10:00:00',
            'ends_at_local' => '11:00:00',
            'is_active' => true,
        ]);

        $this->travelTo('2026-05-03 12:00:00');

        $response = $this->getJson('http://'.$host.'/api/tenant/scheduling/bookable-services/'.$service->id.'/slots?from=2026-05-04&to=2026-05-04');

        $response->assertOk();
        $response->assertJsonStructure(['slots', 'warnings']);
        $slots = $response->json('slots');
        $this->assertNotEmpty($slots);
        $this->assertSame($resource->id, $slots[0]['scheduling_resource_id']);
    }
}
