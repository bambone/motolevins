<?php

declare(strict_types=1);

namespace Tests\Feature\Scheduling\Rental;

use App\Models\CalendarConnection;
use App\Models\CalendarOccupancyMapping;
use App\Models\CalendarSubscription;
use App\Models\ExternalBusyBlock;
use App\Models\Motorcycle;
use App\Models\RentalUnit;
use App\Models\SchedulingResource;
use App\Models\SchedulingTarget;
use App\Scheduling\Enums\AssignmentStrategy;
use App\Scheduling\Enums\CalendarAccessMode;
use App\Scheduling\Enums\CalendarProviderType;
use App\Scheduling\Enums\ExternalBusyEffect;
use App\Scheduling\Enums\MatchConfidence;
use App\Scheduling\Enums\MatchMode;
use App\Scheduling\Enums\OccupancyMappingType;
use App\Scheduling\Enums\SchedulingResourceType;
use App\Scheduling\Enums\SchedulingScope;
use App\Scheduling\Enums\SchedulingTargetType;
use App\Scheduling\Enums\StaleBusyPolicy;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

final class RentalSchedulingBridgeTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    public function test_hard_block_external_busy_makes_rental_unavailable(): void
    {
        $tenant = $this->createTenantWithActiveDomain('rent_bridge');
        $m = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Bike',
            'slug' => 'bike-rb',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 1000,
        ]);
        $unit = RentalUnit::query()->create([
            'tenant_id' => $tenant->id,
            'motorcycle_id' => $m->id,
            'status' => 'active',
        ]);

        $resource = SchedulingResource::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'resource_type' => SchedulingResourceType::Vehicle,
            'label' => 'Unit calendar',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        $target = SchedulingTarget::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'target_type' => SchedulingTargetType::RentalUnit,
            'target_id' => $unit->id,
            'label' => 'Rental '.$unit->id,
            'scheduling_enabled' => false,
            'external_busy_enabled' => true,
            'internal_busy_enabled' => true,
            'auto_write_to_calendar_enabled' => false,
            'external_busy_effect' => ExternalBusyEffect::HardBlock,
            'stale_busy_policy' => null,
            'is_active' => true,
        ]);

        $target->schedulingResources()->attach($resource->id, [
            'priority' => 0,
            'is_default' => true,
            'assignment_strategy' => AssignmentStrategy::FirstAvailable->value,
        ]);

        ExternalBusyBlock::query()->create([
            'scheduling_resource_id' => $resource->id,
            'scheduling_target_id' => $target->id,
            'calendar_subscription_id' => null,
            'starts_at_utc' => Carbon::parse('2026-06-10 10:00:00', 'UTC'),
            'ends_at_utc' => Carbon::parse('2026-06-10 14:00:00', 'UTC'),
            'source_event_id' => 'ext-1',
            'is_tentative' => false,
            'raw_payload' => null,
        ]);

        $start = Carbon::parse('2026-06-10 11:00:00', 'UTC');
        $end = Carbon::parse('2026-06-10 12:00:00', 'UTC');

        $this->assertFalse(app(AvailabilityService::class)->isAvailable($unit, $start, $end));
    }

    public function test_informational_external_busy_does_not_block_rental(): void
    {
        $tenant = $this->createTenantWithActiveDomain('rent_bridge_soft');
        $m = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Bike2',
            'slug' => 'bike-rb2',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 1000,
        ]);
        $unit = RentalUnit::query()->create([
            'tenant_id' => $tenant->id,
            'motorcycle_id' => $m->id,
            'status' => 'active',
        ]);

        $resource = SchedulingResource::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'resource_type' => SchedulingResourceType::Vehicle,
            'label' => 'Unit calendar 2',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        $target = SchedulingTarget::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'target_type' => SchedulingTargetType::RentalUnit,
            'target_id' => $unit->id,
            'label' => 'Rental '.$unit->id,
            'scheduling_enabled' => false,
            'external_busy_enabled' => true,
            'internal_busy_enabled' => true,
            'auto_write_to_calendar_enabled' => false,
            'external_busy_effect' => ExternalBusyEffect::InformationalOnly,
            'stale_busy_policy' => null,
            'is_active' => true,
        ]);

        $target->schedulingResources()->attach($resource->id, [
            'priority' => 0,
            'is_default' => true,
            'assignment_strategy' => AssignmentStrategy::FirstAvailable->value,
        ]);

        ExternalBusyBlock::query()->create([
            'scheduling_resource_id' => $resource->id,
            'scheduling_target_id' => $target->id,
            'calendar_subscription_id' => null,
            'starts_at_utc' => Carbon::parse('2026-06-11 10:00:00', 'UTC'),
            'ends_at_utc' => Carbon::parse('2026-06-11 14:00:00', 'UTC'),
            'source_event_id' => 'ext-2',
            'is_tentative' => false,
            'raw_payload' => null,
        ]);

        $start = Carbon::parse('2026-06-11 11:00:00', 'UTC');
        $end = Carbon::parse('2026-06-11 12:00:00', 'UTC');

        $this->assertTrue(app(AvailabilityService::class)->isAvailable($unit, $start, $end));
    }

    public function test_stale_policy_blocks_when_subscription_stale(): void
    {
        $tenant = $this->createTenantWithActiveDomain('rent_bridge_stale');
        $tenant->update([
            'scheduling_stale_busy_policy' => StaleBusyPolicy::BlockNewSlots,
        ]);

        $m = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Bike3',
            'slug' => 'bike-rb3',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 1000,
        ]);
        $unit = RentalUnit::query()->create([
            'tenant_id' => $tenant->id,
            'motorcycle_id' => $m->id,
            'status' => 'active',
        ]);

        $resource = SchedulingResource::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'resource_type' => SchedulingResourceType::Vehicle,
            'label' => 'Unit calendar 3',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        $target = SchedulingTarget::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'target_type' => SchedulingTargetType::RentalUnit,
            'target_id' => $unit->id,
            'label' => 'Rental '.$unit->id,
            'scheduling_enabled' => false,
            'external_busy_enabled' => true,
            'internal_busy_enabled' => true,
            'auto_write_to_calendar_enabled' => false,
            'external_busy_effect' => ExternalBusyEffect::HardBlock,
            'stale_busy_policy' => null,
            'is_active' => true,
        ]);

        $target->schedulingResources()->attach($resource->id, [
            'priority' => 0,
            'is_default' => true,
            'assignment_strategy' => AssignmentStrategy::FirstAvailable->value,
        ]);

        $connection = CalendarConnection::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'scheduling_resource_id' => null,
            'provider' => CalendarProviderType::Google,
            'access_mode' => CalendarAccessMode::Oauth,
            'account_email' => null,
            'display_name' => 'Test',
            'credentials_encrypted' => null,
            'status' => 'active',
            'is_active' => true,
        ]);

        $sub = CalendarSubscription::query()->create([
            'calendar_connection_id' => $connection->id,
            'external_calendar_id' => 'primary',
            'title' => 'Primary',
            'use_for_busy' => true,
            'use_for_write' => false,
            'is_active' => true,
            'last_successful_sync_at' => Carbon::parse('2020-01-01 00:00:00', 'UTC'),
            'stale_after_seconds' => 3600,
        ]);

        CalendarOccupancyMapping::query()->create([
            'calendar_subscription_id' => $sub->id,
            'mapping_type' => OccupancyMappingType::CalendarToTarget,
            'scheduling_target_id' => $target->id,
            'scheduling_resource_id' => null,
            'match_mode' => MatchMode::EntireCalendar,
            'match_confidence' => MatchConfidence::High,
            'rules_json' => null,
            'is_active' => true,
        ]);

        $this->travelTo('2026-06-12 12:00:00');

        $start = Carbon::parse('2026-06-12 13:00:00', 'UTC');
        $end = Carbon::parse('2026-06-12 14:00:00', 'UTC');

        $this->assertFalse(app(AvailabilityService::class)->isAvailable($unit, $start, $end));
    }
}
