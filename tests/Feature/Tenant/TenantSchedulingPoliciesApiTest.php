<?php

namespace Tests\Feature\Tenant;

use App\Models\AvailabilityRule;
use App\Models\BookableService;
use App\Models\CalendarConnection;
use App\Models\SchedulingResource;
use App\Scheduling\Enums\AssignmentStrategy;
use App\Scheduling\Enums\AvailabilityRuleType;
use App\Scheduling\Enums\CalendarAccessMode;
use App\Scheduling\Enums\CalendarProviderType;
use App\Scheduling\Enums\IntegrationErrorPolicy;
use App\Scheduling\Enums\SchedulingResourceType;
use App\Scheduling\Enums\SchedulingScope;
use App\Scheduling\Enums\SchedulingTargetType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

class TenantSchedulingPoliciesApiTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    public function test_slots_empty_when_integration_error_policy_blocks(): void
    {
        $tenant = $this->createTenantWithActiveDomain('schedpol');
        $host = $this->tenancyHostForSlug('schedpol');
        $tenant->update(['scheduling_integration_error_policy' => IntegrationErrorPolicy::BlockScheduling]);

        CalendarConnection::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'scheduling_resource_id' => null,
            'provider' => CalendarProviderType::Google,
            'access_mode' => CalendarAccessMode::Oauth,
            'display_name' => 'Broken',
            'is_active' => true,
            'last_error' => 'token expired',
        ]);

        $service = BookableService::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'slug' => 'svc',
            'title' => 'Svc',
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
            'resource_type' => SchedulingResourceType::Person,
            'label' => 'A',
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
            'rule_type' => AvailabilityRuleType::WeeklyOpen,
            'weekday' => 1,
            'starts_at_local' => '10:00:00',
            'ends_at_local' => '18:00:00',
            'is_active' => true,
        ]);

        $this->travelTo('2026-05-03 12:00:00');

        $response = $this->getJson('http://'.$host.'/api/tenant/scheduling/bookable-services/'.$service->id.'/slots?from=2026-05-04&to=2026-05-04');
        $response->assertOk();
        $this->assertSame([], $response->json('slots'));
    }

    public function test_slots_empty_when_only_manual_after_request_resources(): void
    {
        $tenant = $this->createTenantWithActiveDomain('schedman');
        $host = $this->tenancyHostForSlug('schedman');

        $service = BookableService::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'slug' => 'svc2',
            'title' => 'Svc2',
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
            'resource_type' => SchedulingResourceType::Person,
            'label' => 'Manual only',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        $target->schedulingResources()->attach($resource->id, [
            'priority' => 0,
            'is_default' => true,
            'assignment_strategy' => AssignmentStrategy::ManualAfterRequest->value,
        ]);

        AvailabilityRule::query()->create([
            'scheduling_resource_id' => $resource->id,
            'rule_type' => AvailabilityRuleType::WeeklyOpen,
            'weekday' => 1,
            'starts_at_local' => '10:00:00',
            'ends_at_local' => '18:00:00',
            'is_active' => true,
        ]);

        $this->travelTo('2026-05-03 12:00:00');

        $response = $this->getJson('http://'.$host.'/api/tenant/scheduling/bookable-services/'.$service->id.'/slots?from=2026-05-04&to=2026-05-04');
        $response->assertOk();
        $this->assertSame([], $response->json('slots'));
    }
}
