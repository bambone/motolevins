<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\AvailabilityException;
use App\Models\AvailabilityRule;
use App\Models\BookableService;
use App\Models\CalendarConnection;
use App\Models\CalendarOccupancyMapping;
use App\Models\CalendarSubscription;
use App\Models\ExternalBusyBlock;
use App\Models\ManualBusyBlock;
use App\Models\SchedulingResource;
use App\Models\SchedulingTarget;
use App\Models\Tenant;
use App\Scheduling\Enums\AssignmentStrategy;
use App\Scheduling\Enums\AvailabilityExceptionType;
use App\Scheduling\Enums\AvailabilityRuleType;
use App\Scheduling\Enums\CalendarAccessMode;
use App\Scheduling\Enums\CalendarProviderType;
use App\Scheduling\Enums\CalendarUsageMode;
use App\Scheduling\Enums\ManualBusySeverity;
use App\Scheduling\Enums\ManualBusySource;
use App\Scheduling\Enums\OccupancyMappingType;
use App\Scheduling\Enums\SchedulingResourceType;
use App\Scheduling\Enums\SchedulingScope;
use App\Scheduling\Enums\SchedulingTargetType;
use App\Scheduling\Enums\UnconfirmedRequestsPolicy;
use Carbon\Carbon;

/**
 * Фикстуры для доменных тестов scheduling / occupancy.
 */
trait SchedulingTestScenarios
{
    protected function schedulingCreateBookableService(Tenant $tenant, array $overrides = []): BookableService
    {
        return BookableService::query()->create(array_merge([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'slug' => 'svc-'.uniqid(),
            'title' => 'Service',
            'duration_minutes' => 30,
            'slot_step_minutes' => 30,
            'buffer_before_minutes' => 0,
            'buffer_after_minutes' => 0,
            'min_booking_notice_minutes' => 0,
            'max_booking_horizon_days' => 30,
            'requires_confirmation' => false,
            'is_active' => true,
            'sort_weight' => 0,
            'sync_title_from_source' => true,
        ], $overrides));
    }

    protected function schedulingEnableTargetForService(BookableService $service, array $targetAttrs = []): SchedulingTarget
    {
        $target = $service->schedulingTarget;
        if ($target === null) {
            throw new \RuntimeException('schedulingTarget missing after service create');
        }
        $target->update(array_merge([
            'scheduling_enabled' => true,
            'target_type' => SchedulingTargetType::BookableService,
            'target_id' => $service->id,
            'external_busy_enabled' => false,
            'internal_busy_enabled' => true,
            'calendar_usage_mode' => CalendarUsageMode::Disabled,
        ], $targetAttrs));

        return $target->fresh();
    }

    protected function schedulingCreateResource(Tenant $tenant, array $overrides = []): SchedulingResource
    {
        return SchedulingResource::query()->create(array_merge([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'resource_type' => SchedulingResourceType::Person,
            'label' => 'Resource',
            'timezone' => 'UTC',
            'unconfirmed_requests_policy' => UnconfirmedRequestsPolicy::ConfirmedOnly,
            'is_active' => true,
        ], $overrides));
    }

    /**
     * @param  array{priority?: int, is_default?: bool, assignment_strategy?: AssignmentStrategy}  $pivot
     */
    protected function schedulingAttachResource(SchedulingTarget $target, SchedulingResource $resource, array $pivot = []): void
    {
        $target->schedulingResources()->attach($resource->id, [
            'priority' => $pivot['priority'] ?? 0,
            'is_default' => $pivot['is_default'] ?? true,
            'assignment_strategy' => ($pivot['assignment_strategy'] ?? AssignmentStrategy::FirstAvailable)->value,
        ]);
    }

    protected function schedulingWeeklyOpenRule(
        SchedulingResource $resource,
        int $weekdayIso,
        string $startLocal,
        string $endLocal,
        ?SchedulingTarget $narrowTarget = null,
        ?BookableService $narrowService = null,
    ): AvailabilityRule {
        return AvailabilityRule::query()->create([
            'scheduling_resource_id' => $resource->id,
            'applies_to_scheduling_target_id' => $narrowTarget?->id,
            'applies_to_bookable_service_id' => $narrowService?->id,
            'rule_type' => AvailabilityRuleType::WeeklyOpen,
            'weekday' => $weekdayIso,
            'starts_at_local' => $startLocal,
            'ends_at_local' => $endLocal,
            'is_active' => true,
        ]);
    }

    protected function schedulingException(
        SchedulingResource $resource,
        AvailabilityExceptionType $type,
        Carbon $startsUtc,
        Carbon $endsUtc,
        ?SchedulingTarget $target = null,
        ?BookableService $service = null,
    ): AvailabilityException {
        return AvailabilityException::query()->create([
            'scheduling_resource_id' => $resource->id,
            'scheduling_target_id' => $target?->id,
            'bookable_service_id' => $service?->id,
            'exception_type' => $type,
            'starts_at_utc' => $startsUtc,
            'ends_at_utc' => $endsUtc,
        ]);
    }

    protected function schedulingManualBusy(
        Tenant $tenant,
        SchedulingResource $resource,
        Carbon $startUtc,
        Carbon $endUtc,
        ?SchedulingTarget $target = null,
    ): ManualBusyBlock {
        return ManualBusyBlock::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'scheduling_target_id' => $target?->id,
            'scheduling_resource_id' => $resource->id,
            'starts_at_utc' => $startUtc,
            'ends_at_utc' => $endUtc,
            'reason' => 'test',
            'severity' => ManualBusySeverity::Hard,
            'source' => ManualBusySource::Operator,
        ]);
    }

    protected function schedulingExternalBusy(
        SchedulingResource $resource,
        Carbon $startUtc,
        Carbon $endUtc,
        bool $tentative = false,
        ?int $schedulingTargetId = null,
        ?int $calendarSubscriptionId = null,
        ?string $sourceEventId = null,
    ): ExternalBusyBlock {
        return ExternalBusyBlock::query()->create([
            'scheduling_resource_id' => $resource->id,
            'scheduling_target_id' => $schedulingTargetId,
            'calendar_subscription_id' => $calendarSubscriptionId,
            'starts_at_utc' => $startUtc,
            'ends_at_utc' => $endUtc,
            'source_event_id' => $sourceEventId ?? 'ext-'.uniqid(),
            'is_tentative' => $tentative,
            'raw_payload' => null,
        ]);
    }

    /**
     * @return array{connection: CalendarConnection, subscription: CalendarSubscription}
     */
    protected function schedulingCalendarSubscription(Tenant $tenant, array $subOverrides = [], array $connOverrides = []): array
    {
        $connection = CalendarConnection::query()->create(array_merge([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'provider' => CalendarProviderType::Google,
            'access_mode' => CalendarAccessMode::Oauth,
            'display_name' => 'Conn',
            'is_active' => true,
        ], $connOverrides));

        $subscription = CalendarSubscription::query()->create(array_merge([
            'calendar_connection_id' => $connection->id,
            'external_calendar_id' => 'primary',
            'use_for_busy' => true,
            'use_for_write' => true,
            'is_active' => true,
        ], $subOverrides));

        return ['connection' => $connection, 'subscription' => $subscription];
    }

    protected function schedulingOccupancyMapping(
        CalendarSubscription $subscription,
        SchedulingTarget $target,
        array $overrides = [],
    ): CalendarOccupancyMapping {
        return CalendarOccupancyMapping::query()->create(array_merge([
            'calendar_subscription_id' => $subscription->id,
            'mapping_type' => OccupancyMappingType::CalendarToTarget,
            'scheduling_target_id' => $target->id,
            'scheduling_resource_id' => null,
            'is_active' => true,
        ], $overrides));
    }
}
