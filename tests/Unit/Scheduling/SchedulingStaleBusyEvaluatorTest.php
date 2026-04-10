<?php

declare(strict_types=1);

namespace Tests\Unit\Scheduling;

use App\Models\CalendarConnection;
use App\Models\CalendarOccupancyMapping;
use App\Models\CalendarSubscription;
use App\Models\ExternalBusyBlock;
use App\Models\SchedulingResource;
use App\Models\SchedulingTarget;
use App\Scheduling\Enums\CalendarAccessMode;
use App\Scheduling\Enums\CalendarProviderType;
use App\Scheduling\Enums\OccupancyMappingType;
use App\Scheduling\Enums\SchedulingResourceType;
use App\Scheduling\Enums\SchedulingScope;
use App\Scheduling\Enums\SchedulingTargetType;
use App\Scheduling\SchedulingStaleBusyEvaluator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

final class SchedulingStaleBusyEvaluatorTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    private function targetWithResource(): array
    {
        $tenant = $this->createTenantWithActiveDomain('stale_eval');
        $target = SchedulingTarget::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'target_type' => SchedulingTargetType::BookableService,
            'target_id' => 1,
            'label' => 'T',
            'scheduling_enabled' => true,
            'external_busy_enabled' => false,
            'internal_busy_enabled' => true,
            'auto_write_to_calendar_enabled' => false,
            'is_active' => true,
        ]);
        $resource = SchedulingResource::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'resource_type' => SchedulingResourceType::Person,
            'label' => 'R',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);
        $target->schedulingResources()->attach($resource->id, [
            'priority' => 0,
            'is_default' => true,
            'assignment_strategy' => 'first_available',
        ]);

        return [$tenant, $target, $resource];
    }

    private function makeSubscription(
        int $tenantId,
        ?Carbon $lastSync,
        ?int $staleAfter,
        bool $useForBusy = true,
        bool $isActive = true,
    ): CalendarSubscription {
        $conn = CalendarConnection::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenantId,
            'provider' => CalendarProviderType::Google,
            'access_mode' => CalendarAccessMode::Oauth,
            'display_name' => 'C',
            'is_active' => true,
        ]);

        return CalendarSubscription::query()->create([
            'calendar_connection_id' => $conn->id,
            'external_calendar_id' => 'primary',
            'use_for_busy' => $useForBusy,
            'use_for_write' => false,
            'is_active' => $isActive,
            'last_successful_sync_at' => $lastSync,
            'stale_after_seconds' => $staleAfter,
        ]);
    }

    public function test_no_linked_subscriptions_is_not_stale(): void
    {
        [, $target] = $this->targetWithResource();
        $this->assertFalse(app(SchedulingStaleBusyEvaluator::class)->subscriptionsAreStaleForTarget($target));
    }

    public function test_null_last_sync_with_positive_threshold_is_stale(): void
    {
        [$tenant, $target, $resource] = $this->targetWithResource();
        $sub = $this->makeSubscription($tenant->id, null, 3600);
        CalendarOccupancyMapping::query()->create([
            'calendar_subscription_id' => $sub->id,
            'mapping_type' => OccupancyMappingType::CalendarToTarget,
            'scheduling_target_id' => $target->id,
            'is_active' => true,
        ]);

        $this->assertTrue(app(SchedulingStaleBusyEvaluator::class)->subscriptionsAreStaleForTarget($target));
    }

    public function test_recent_sync_within_stale_window_is_fresh(): void
    {
        $this->travelTo('2026-06-01 12:00:00');
        [$tenant, $target] = $this->targetWithResource();
        $sub = $this->makeSubscription($tenant->id, Carbon::parse('2026-06-01 11:30:00', 'UTC'), 3600);
        CalendarOccupancyMapping::query()->create([
            'calendar_subscription_id' => $sub->id,
            'mapping_type' => OccupancyMappingType::CalendarToTarget,
            'scheduling_target_id' => $target->id,
            'is_active' => true,
        ]);

        $this->assertFalse(app(SchedulingStaleBusyEvaluator::class)->subscriptionsAreStaleForTarget($target));
    }

    public function test_old_sync_outside_stale_window_is_stale(): void
    {
        $this->travelTo('2026-06-01 12:00:00');
        [$tenant, $target] = $this->targetWithResource();
        $sub = $this->makeSubscription($tenant->id, Carbon::parse('2026-06-01 09:00:00', 'UTC'), 3600);
        CalendarOccupancyMapping::query()->create([
            'calendar_subscription_id' => $sub->id,
            'mapping_type' => OccupancyMappingType::CalendarToTarget,
            'scheduling_target_id' => $target->id,
            'is_active' => true,
        ]);

        $this->assertTrue(app(SchedulingStaleBusyEvaluator::class)->subscriptionsAreStaleForTarget($target));
    }

    public function test_subscription_from_external_busy_block_is_considered(): void
    {
        $this->travelTo('2026-06-01 12:00:00');
        [$tenant, $target, $resource] = $this->targetWithResource();
        $sub = $this->makeSubscription($tenant->id, Carbon::parse('2020-01-01 00:00:00', 'UTC'), 60);
        ExternalBusyBlock::query()->create([
            'scheduling_resource_id' => $resource->id,
            'scheduling_target_id' => $target->id,
            'calendar_subscription_id' => $sub->id,
            'starts_at_utc' => Carbon::parse('2026-06-01 10:00:00', 'UTC'),
            'ends_at_utc' => Carbon::parse('2026-06-01 11:00:00', 'UTC'),
            'source_event_id' => 'e1',
            'is_tentative' => false,
        ]);

        $this->assertTrue(app(SchedulingStaleBusyEvaluator::class)->subscriptionsAreStaleForTarget($target));
    }

    public function test_inactive_subscription_skipped(): void
    {
        $this->travelTo('2026-06-01 12:00:00');
        [$tenant, $target] = $this->targetWithResource();
        $sub = $this->makeSubscription($tenant->id, null, 60, true, false);
        CalendarOccupancyMapping::query()->create([
            'calendar_subscription_id' => $sub->id,
            'mapping_type' => OccupancyMappingType::CalendarToTarget,
            'scheduling_target_id' => $target->id,
            'is_active' => true,
        ]);

        $this->assertFalse(app(SchedulingStaleBusyEvaluator::class)->subscriptionsAreStaleForTarget($target));
    }

    public function test_use_for_busy_false_skipped(): void
    {
        $this->travelTo('2026-06-01 12:00:00');
        [$tenant, $target] = $this->targetWithResource();
        $sub = $this->makeSubscription($tenant->id, null, 60, false, true);
        CalendarOccupancyMapping::query()->create([
            'calendar_subscription_id' => $sub->id,
            'mapping_type' => OccupancyMappingType::CalendarToTarget,
            'scheduling_target_id' => $target->id,
            'is_active' => true,
        ]);

        $this->assertFalse(app(SchedulingStaleBusyEvaluator::class)->subscriptionsAreStaleForTarget($target));
    }
}
