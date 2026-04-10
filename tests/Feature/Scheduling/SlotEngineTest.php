<?php

declare(strict_types=1);

namespace Tests\Feature\Scheduling;

use App\Models\AppointmentHold;
use App\Models\AvailabilityRule;
use App\Models\CalendarConnection;
use App\Models\CalendarOccupancyMapping;
use App\Models\CalendarSubscription;
use App\Models\Tenant;
use App\Scheduling\Enums\AppointmentHoldStatus;
use App\Scheduling\Enums\AssignmentStrategy;
use App\Scheduling\Enums\AvailabilityExceptionType;
use App\Scheduling\Enums\AvailabilityRuleType;
use App\Scheduling\Enums\CalendarAccessMode;
use App\Scheduling\Enums\CalendarProviderType;
use App\Scheduling\Enums\CalendarUsageMode;
use App\Scheduling\Enums\IntegrationErrorPolicy;
use App\Scheduling\Enums\OccupancyMappingType;
use App\Scheduling\Enums\SchedulingScope;
use App\Scheduling\Enums\StaleBusyPolicy;
use App\Scheduling\Enums\TentativeEventsPolicy;
use App\Scheduling\Enums\UnconfirmedRequestsPolicy;
use App\Scheduling\SlotEngineService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\Support\SchedulingTestScenarios;
use Tests\TestCase;

final class SlotEngineTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;
    use SchedulingTestScenarios;

    private function baseServiceWithResource(Tenant $tenant, array $serviceOverrides = [], array $targetOverrides = []): array
    {
        $service = $this->schedulingCreateBookableService($tenant, $serviceOverrides);
        $target = $this->schedulingEnableTargetForService($service, $targetOverrides);
        $resource = $this->schedulingCreateResource($tenant, ['timezone' => 'UTC']);
        $this->schedulingAttachResource($target, $resource);

        return [$service, $target, $resource];
    }

    public function test_builds_slots_from_weekly_rules(): void
    {
        $tenant = $this->createTenantWithActiveDomain('slot_eng_weekly');
        [$service, $target, $resource] = $this->baseServiceWithResource($tenant);
        $this->schedulingWeeklyOpenRule($resource, 1, '10:00:00', '12:00:00');

        $this->travelTo(Carbon::parse('2026-05-03 12:00:00', 'UTC'));
        $from = Carbon::parse('2026-05-04 00:00:00', 'UTC')->startOfDay();
        $to = Carbon::parse('2026-05-04 00:00:00', 'UTC')->endOfDay();

        $slots = app(SlotEngineService::class)->slotsForBookableService($service, $from, $to);
        $this->assertNotEmpty($slots);
        $this->assertSame($resource->id, $slots[0]['scheduling_resource_id']);
        $this->assertSame('2026-05-04T10:00:00+00:00', $slots[0]['starts_at_utc']);
    }

    public function test_open_exception_extends_availability(): void
    {
        $tenant = $this->createTenantWithActiveDomain('slot_eng_open_ex');
        [$service, $target, $resource] = $this->baseServiceWithResource($tenant);
        $this->schedulingWeeklyOpenRule($resource, 1, '10:00:00', '11:00:00');
        $this->schedulingException(
            $resource,
            AvailabilityExceptionType::Open,
            Carbon::parse('2026-05-04 14:00:00', 'UTC'),
            Carbon::parse('2026-05-04 15:00:00', 'UTC'),
            $target,
            $service,
        );

        $this->travelTo(Carbon::parse('2026-05-03 12:00:00', 'UTC'));
        $from = Carbon::parse('2026-05-04 00:00:00', 'UTC')->startOfDay();
        $to = Carbon::parse('2026-05-04 00:00:00', 'UTC')->endOfDay();

        $slots = app(SlotEngineService::class)->slotsForBookableService($service, $from, $to);
        $afternoon = array_values(array_filter($slots, fn (array $s): bool => str_contains($s['starts_at_utc'], 'T14:')));
        $this->assertNotEmpty($afternoon);
    }

    public function test_closed_exception_removes_window(): void
    {
        $tenant = $this->createTenantWithActiveDomain('slot_eng_closed_ex');
        [$service, $target, $resource] = $this->baseServiceWithResource($tenant);
        $this->schedulingWeeklyOpenRule($resource, 1, '08:00:00', '18:00:00');
        $this->schedulingException(
            $resource,
            AvailabilityExceptionType::Closed,
            Carbon::parse('2026-05-04 10:00:00', 'UTC'),
            Carbon::parse('2026-05-04 12:00:00', 'UTC'),
            $target,
            $service,
        );

        $this->travelTo(Carbon::parse('2026-05-03 12:00:00', 'UTC'));
        $from = Carbon::parse('2026-05-04 00:00:00', 'UTC')->startOfDay();
        $to = Carbon::parse('2026-05-04 00:00:00', 'UTC')->endOfDay();

        $slots = app(SlotEngineService::class)->slotsForBookableService($service, $from, $to);
        foreach ($slots as $slot) {
            $start = Carbon::parse($slot['starts_at_utc']);
            $this->assertTrue($start->lt(Carbon::parse('2026-05-04 10:00:00', 'UTC'))
                || $start->gte(Carbon::parse('2026-05-04 12:00:00', 'UTC')));
        }
    }

    public function test_manual_busy_subtracts_slots(): void
    {
        $tenant = $this->createTenantWithActiveDomain('slot_eng_manual');
        [$service, $target, $resource] = $this->baseServiceWithResource($tenant);
        $this->schedulingWeeklyOpenRule($resource, 1, '10:00:00', '12:00:00');
        $this->schedulingManualBusy(
            $tenant,
            $resource,
            Carbon::parse('2026-05-04 10:00:00', 'UTC'),
            Carbon::parse('2026-05-04 11:00:00', 'UTC'),
            $target,
        );

        $this->travelTo(Carbon::parse('2026-05-03 12:00:00', 'UTC'));
        $from = Carbon::parse('2026-05-04 00:00:00', 'UTC')->startOfDay();
        $to = Carbon::parse('2026-05-04 00:00:00', 'UTC')->endOfDay();

        $slots = app(SlotEngineService::class)->slotsForBookableService($service, $from, $to);
        $this->assertNotEmpty($slots);
        foreach ($slots as $slot) {
            $this->assertNotSame('2026-05-04T10:00:00+00:00', $slot['starts_at_utc']);
        }
    }

    public function test_appointment_hold_blocks_when_policy_counts_pending_and_confirmed(): void
    {
        $tenant = $this->createTenantWithActiveDomain('slot_eng_hold');
        [$service, $target, $resource] = $this->baseServiceWithResource($tenant);
        $resource->update(['unconfirmed_requests_policy' => UnconfirmedRequestsPolicy::PendingAndConfirmedAreBusy]);
        $this->schedulingWeeklyOpenRule($resource, 1, '10:00:00', '12:00:00');

        AppointmentHold::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'bookable_service_id' => $service->id,
            'scheduling_resource_id' => $resource->id,
            'starts_at_utc' => Carbon::parse('2026-05-04 10:00:00', 'UTC'),
            'ends_at_utc' => Carbon::parse('2026-05-04 10:30:00', 'UTC'),
            'status' => AppointmentHoldStatus::Confirmed,
            'source' => 'test',
            'expires_at' => Carbon::parse('2026-05-05 00:00:00', 'UTC'),
        ]);

        $this->travelTo(Carbon::parse('2026-05-03 12:00:00', 'UTC'));
        $from = Carbon::parse('2026-05-04 00:00:00', 'UTC')->startOfDay();
        $to = Carbon::parse('2026-05-04 00:00:00', 'UTC')->endOfDay();

        $slots = app(SlotEngineService::class)->slotsForBookableService($service, $from, $to);
        foreach ($slots as $slot) {
            $this->assertNotSame('2026-05-04T10:00:00+00:00', $slot['starts_at_utc']);
        }
    }

    public function test_external_busy_subtracted_when_read_mode_and_enabled(): void
    {
        $tenant = $this->createTenantWithActiveDomain('slot_eng_ext_read');
        [$service, $target, $resource] = $this->baseServiceWithResource($tenant, [], [
            'external_busy_enabled' => true,
            'calendar_usage_mode' => CalendarUsageMode::ReadBusyOnly,
        ]);
        $this->schedulingWeeklyOpenRule($resource, 1, '10:00:00', '12:00:00');
        $this->schedulingExternalBusy(
            $resource,
            Carbon::parse('2026-05-04 10:00:00', 'UTC'),
            Carbon::parse('2026-05-04 11:00:00', 'UTC'),
        );

        $this->travelTo(Carbon::parse('2026-05-03 12:00:00', 'UTC'));
        $from = Carbon::parse('2026-05-04 00:00:00', 'UTC')->startOfDay();
        $to = Carbon::parse('2026-05-04 00:00:00', 'UTC')->endOfDay();

        $slots = app(SlotEngineService::class)->slotsForBookableService($service, $from, $to);
        foreach ($slots as $slot) {
            $this->assertNotSame('2026-05-04T10:00:00+00:00', $slot['starts_at_utc']);
        }
    }

    public function test_external_busy_not_subtracted_for_write_only_or_disabled_usage_mode(): void
    {
        foreach ([CalendarUsageMode::WriteOnly, CalendarUsageMode::Disabled] as $mode) {
            $tenant = $this->createTenantWithActiveDomain('slot_eng_wr_'.$mode->value);
            [$service, $target, $resource] = $this->baseServiceWithResource($tenant, [], [
                'external_busy_enabled' => true,
                'calendar_usage_mode' => $mode,
            ]);
            $this->schedulingWeeklyOpenRule($resource, 1, '10:00:00', '12:00:00');
            $this->schedulingExternalBusy(
                $resource,
                Carbon::parse('2026-05-04 10:00:00', 'UTC'),
                Carbon::parse('2026-05-04 11:00:00', 'UTC'),
            );

            $this->travelTo(Carbon::parse('2026-05-03 12:00:00', 'UTC'));
            $from = Carbon::parse('2026-05-04 00:00:00', 'UTC')->startOfDay();
            $to = Carbon::parse('2026-05-04 00:00:00', 'UTC')->endOfDay();

            $slots = app(SlotEngineService::class)->slotsForBookableService($service, $from, $to);
            $hasTen = collect($slots)->contains(fn (array $s): bool => $s['starts_at_utc'] === '2026-05-04T10:00:00+00:00');
            $this->assertTrue($hasTen, 'Expected 10:00 slot when external busy ignored for '.$mode->value);
        }
    }

    public function test_external_busy_not_subtracted_when_external_busy_disabled(): void
    {
        $tenant = $this->createTenantWithActiveDomain('slot_eng_ext_off');
        [$service, $target, $resource] = $this->baseServiceWithResource($tenant, [], [
            'external_busy_enabled' => false,
            'calendar_usage_mode' => CalendarUsageMode::ReadBusyOnly,
        ]);
        $this->schedulingWeeklyOpenRule($resource, 1, '10:00:00', '12:00:00');
        $this->schedulingExternalBusy(
            $resource,
            Carbon::parse('2026-05-04 10:00:00', 'UTC'),
            Carbon::parse('2026-05-04 11:00:00', 'UTC'),
        );

        $this->travelTo(Carbon::parse('2026-05-03 12:00:00', 'UTC'));
        $from = Carbon::parse('2026-05-04 00:00:00', 'UTC')->startOfDay();
        $to = Carbon::parse('2026-05-04 00:00:00', 'UTC')->endOfDay();

        $slots = app(SlotEngineService::class)->slotsForBookableService($service, $from, $to);
        $this->assertTrue(collect($slots)->contains(fn (array $s): bool => $s['starts_at_utc'] === '2026-05-04T10:00:00+00:00'));
    }

    public function test_duration_buffers_step_and_notice(): void
    {
        $tenant = $this->createTenantWithActiveDomain('slot_eng_buf');
        [$service, $target, $resource] = $this->baseServiceWithResource($tenant, [
            'duration_minutes' => 30,
            'slot_step_minutes' => 30,
            'buffer_before_minutes' => 15,
            'buffer_after_minutes' => 15,
            'min_booking_notice_minutes' => 0,
        ]);
        $this->schedulingWeeklyOpenRule($resource, 1, '10:00:00', '12:00:00');

        $this->travelTo(Carbon::parse('2026-05-03 12:00:00', 'UTC'));
        $from = Carbon::parse('2026-05-04 00:00:00', 'UTC')->startOfDay();
        $to = Carbon::parse('2026-05-04 00:00:00', 'UTC')->endOfDay();

        $slots = app(SlotEngineService::class)->slotsForBookableService($service, $from, $to);
        $this->assertCount(3, $slots);
        $this->assertSame('2026-05-04T10:15:00+00:00', $slots[0]['starts_at_utc']);
        $this->assertSame('2026-05-04T11:15:00+00:00', $slots[2]['starts_at_utc']);

        $service->update(['min_booking_notice_minutes' => 24 * 60]);
        $slots2 = app(SlotEngineService::class)->slotsForBookableService($service, $from, $to);
        $this->assertSame([], $slots2);
    }

    public function test_max_booking_horizon_caps_range(): void
    {
        $tenant = $this->createTenantWithActiveDomain('slot_eng_horizon');
        [$service, $target, $resource] = $this->baseServiceWithResource($tenant, [
            'max_booking_horizon_days' => 1,
            'min_booking_notice_minutes' => 0,
        ]);
        $this->schedulingWeeklyOpenRule($resource, 1, '10:00:00', '11:00:00');

        $this->travelTo(Carbon::parse('2026-05-03 12:00:00', 'UTC'));
        $farDay = Carbon::parse('2026-05-10 00:00:00', 'UTC');
        $slots = app(SlotEngineService::class)->slotsForBookableService(
            $service,
            $farDay->copy()->startOfDay(),
            $farDay->copy()->endOfDay(),
        );
        $this->assertSame([], $slots);
    }

    public function test_resource_timezone_shifts_slot_utc(): void
    {
        $tenant = $this->createTenantWithActiveDomain('slot_eng_tz');
        $service = $this->schedulingCreateBookableService($tenant);
        $target = $this->schedulingEnableTargetForService($service);
        $resource = $this->schedulingCreateResource($tenant, ['timezone' => 'Europe/Moscow']);
        $this->schedulingAttachResource($target, $resource);
        $this->schedulingWeeklyOpenRule($resource, 1, '10:00:00', '11:00:00');

        $this->travelTo(Carbon::parse('2026-05-03 12:00:00', 'UTC'));
        $from = Carbon::parse('2026-05-04 00:00:00', 'UTC')->startOfDay();
        $to = Carbon::parse('2026-05-04 00:00:00', 'UTC')->endOfDay();

        $slots = app(SlotEngineService::class)->slotsForBookableService($service, $from, $to);
        $this->assertNotEmpty($slots);
        $this->assertSame('2026-05-04T07:00:00+00:00', $slots[0]['starts_at_utc']);
    }

    public function test_pool_union_from_two_resources(): void
    {
        $tenant = $this->createTenantWithActiveDomain('slot_eng_union');
        $service = $this->schedulingCreateBookableService($tenant);
        $target = $this->schedulingEnableTargetForService($service);
        $r1 = $this->schedulingCreateResource($tenant, ['label' => 'A']);
        $r2 = $this->schedulingCreateResource($tenant, ['label' => 'B']);
        $this->schedulingAttachResource($target, $r1, ['priority' => 0]);
        $this->schedulingAttachResource($target, $r2, ['priority' => 1]);
        $this->schedulingWeeklyOpenRule($r1, 1, '10:00:00', '11:00:00');
        $this->schedulingWeeklyOpenRule($r2, 1, '10:00:00', '11:00:00');

        $this->travelTo(Carbon::parse('2026-05-03 12:00:00', 'UTC'));
        $from = Carbon::parse('2026-05-04 00:00:00', 'UTC')->startOfDay();
        $to = Carbon::parse('2026-05-04 00:00:00', 'UTC')->endOfDay();

        $slots = app(SlotEngineService::class)->slotsForBookableService($service, $from, $to);
        $ids = collect($slots)->pluck('scheduling_resource_id')->unique()->sort()->values()->all();
        $this->assertEqualsCanonicalizing([$r1->id, $r2->id], $ids);
    }

    public function test_manual_after_request_resources_excluded_from_public_slots(): void
    {
        $tenant = $this->createTenantWithActiveDomain('slot_eng_manual_req');
        $service = $this->schedulingCreateBookableService($tenant);
        $target = $this->schedulingEnableTargetForService($service);
        $resource = $this->schedulingCreateResource($tenant);
        $this->schedulingAttachResource($target, $resource, [
            'assignment_strategy' => AssignmentStrategy::ManualAfterRequest,
        ]);
        $this->schedulingWeeklyOpenRule($resource, 1, '10:00:00', '12:00:00');

        $this->travelTo(Carbon::parse('2026-05-03 12:00:00', 'UTC'));
        $from = Carbon::parse('2026-05-04 00:00:00', 'UTC')->startOfDay();
        $to = Carbon::parse('2026-05-04 00:00:00', 'UTC')->endOfDay();

        $slots = app(SlotEngineService::class)->slotsForBookableService($service, $from, $to);
        $this->assertSame([], $slots);
    }

    public function test_integration_error_policy_blocks_all_slots(): void
    {
        $tenant = $this->createTenantWithActiveDomain('slot_eng_int_err');
        $tenant->update(['scheduling_integration_error_policy' => IntegrationErrorPolicy::BlockScheduling]);
        CalendarConnection::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'provider' => CalendarProviderType::Google,
            'access_mode' => CalendarAccessMode::Oauth,
            'display_name' => 'Err',
            'is_active' => true,
            'last_error' => 'sync failed',
        ]);

        [$service, $target, $resource] = $this->baseServiceWithResource($tenant);
        $this->schedulingWeeklyOpenRule($resource, 1, '10:00:00', '12:00:00');

        $this->travelTo(Carbon::parse('2026-05-03 12:00:00', 'UTC'));
        $from = Carbon::parse('2026-05-04 00:00:00', 'UTC')->startOfDay();
        $to = Carbon::parse('2026-05-04 00:00:00', 'UTC')->endOfDay();

        $slots = app(SlotEngineService::class)->slotsForBookableService($service, $from, $to);
        $this->assertSame([], $slots);
    }

    public function test_stale_busy_policy_block_new_slots_empties_slots(): void
    {
        $tenant = $this->createTenantWithActiveDomain('slot_eng_stale');
        $tenant->update(['scheduling_stale_busy_policy' => StaleBusyPolicy::BlockNewSlots]);

        [$service, $target, $resource] = $this->baseServiceWithResource($tenant, [], [
            'external_busy_enabled' => true,
            'calendar_usage_mode' => CalendarUsageMode::ReadBusyOnly,
        ]);
        $this->schedulingWeeklyOpenRule($resource, 1, '10:00:00', '12:00:00');

        $conn = CalendarConnection::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'provider' => CalendarProviderType::Google,
            'access_mode' => CalendarAccessMode::Oauth,
            'display_name' => 'Stale',
            'is_active' => true,
        ]);
        $sub = CalendarSubscription::query()->create([
            'calendar_connection_id' => $conn->id,
            'external_calendar_id' => 'primary',
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
            'is_active' => true,
        ]);

        $this->travelTo(Carbon::parse('2026-05-03 12:00:00', 'UTC'));
        $from = Carbon::parse('2026-05-04 00:00:00', 'UTC')->startOfDay();
        $to = Carbon::parse('2026-05-04 00:00:00', 'UTC')->endOfDay();

        $slots = app(SlotEngineService::class)->slotsForBookableService($service, $from, $to);
        $this->assertSame([], $slots);
    }

    public function test_warn_only_stale_still_returns_slots_from_engine(): void
    {
        $tenant = $this->createTenantWithActiveDomain('slot_eng_stale_warn');
        $tenant->update(['scheduling_stale_busy_policy' => StaleBusyPolicy::WarnOnly]);

        [$service, $target, $resource] = $this->baseServiceWithResource($tenant, [], [
            'external_busy_enabled' => true,
            'calendar_usage_mode' => CalendarUsageMode::ReadBusyOnly,
        ]);
        $this->schedulingWeeklyOpenRule($resource, 1, '10:00:00', '12:00:00');

        $conn = CalendarConnection::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'provider' => CalendarProviderType::Google,
            'access_mode' => CalendarAccessMode::Oauth,
            'display_name' => 'StaleW',
            'is_active' => true,
        ]);
        $sub = CalendarSubscription::query()->create([
            'calendar_connection_id' => $conn->id,
            'external_calendar_id' => 'primary',
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
            'is_active' => true,
        ]);

        $this->travelTo(Carbon::parse('2026-05-03 12:00:00', 'UTC'));
        $from = Carbon::parse('2026-05-04 00:00:00', 'UTC')->startOfDay();
        $to = Carbon::parse('2026-05-04 00:00:00', 'UTC')->endOfDay();

        $slots = app(SlotEngineService::class)->slotsForBookableService($service, $from, $to);
        $this->assertNotEmpty($slots);
    }

    public function test_tentative_external_busy_respects_treat_as_free(): void
    {
        $tenant = $this->createTenantWithActiveDomain('slot_eng_tent');
        [$service, $target, $resource] = $this->baseServiceWithResource($tenant, [], [
            'external_busy_enabled' => true,
            'calendar_usage_mode' => CalendarUsageMode::ReadBusyOnly,
        ]);
        $resource->update(['tentative_events_policy' => TentativeEventsPolicy::TreatAsFree]);
        $this->schedulingWeeklyOpenRule($resource, 1, '10:00:00', '12:00:00');
        $this->schedulingExternalBusy(
            $resource,
            Carbon::parse('2026-05-04 10:00:00', 'UTC'),
            Carbon::parse('2026-05-04 11:00:00', 'UTC'),
            true,
        );

        $this->travelTo(Carbon::parse('2026-05-03 12:00:00', 'UTC'));
        $from = Carbon::parse('2026-05-04 00:00:00', 'UTC')->startOfDay();
        $to = Carbon::parse('2026-05-04 00:00:00', 'UTC')->endOfDay();

        $slots = app(SlotEngineService::class)->slotsForBookableService($service, $from, $to);
        $this->assertTrue(collect($slots)->contains(fn (array $s): bool => $s['starts_at_utc'] === '2026-05-04T10:00:00+00:00'));
    }

    public function test_no_weekly_rules_yields_empty_slots(): void
    {
        $tenant = $this->createTenantWithActiveDomain('slot_eng_empty_rules');
        [$service] = $this->baseServiceWithResource($tenant);

        $this->travelTo(Carbon::parse('2026-05-03 12:00:00', 'UTC'));
        $from = Carbon::parse('2026-05-04 00:00:00', 'UTC')->startOfDay();
        $to = Carbon::parse('2026-05-04 00:00:00', 'UTC')->endOfDay();

        $slots = app(SlotEngineService::class)->slotsForBookableService($service, $from, $to);
        $this->assertSame([], $slots);
    }

    public function test_multiple_open_windows_same_day(): void
    {
        $tenant = $this->createTenantWithActiveDomain('slot_eng_multi_win');
        [$service, $target, $resource] = $this->baseServiceWithResource($tenant);
        $this->schedulingWeeklyOpenRule($resource, 1, '09:00:00', '10:00:00');
        $this->schedulingWeeklyOpenRule($resource, 1, '14:00:00', '15:00:00');

        $this->travelTo(Carbon::parse('2026-05-03 12:00:00', 'UTC'));
        $from = Carbon::parse('2026-05-04 00:00:00', 'UTC')->startOfDay();
        $to = Carbon::parse('2026-05-04 00:00:00', 'UTC')->endOfDay();

        $slots = app(SlotEngineService::class)->slotsForBookableService($service, $from, $to);
        $starts = collect($slots)->pluck('starts_at_utc')->sort()->values()->all();
        $this->assertContains('2026-05-04T09:00:00+00:00', $starts);
        $this->assertContains('2026-05-04T14:00:00+00:00', $starts);
    }

    public function test_weekly_closed_rule_carves_midday(): void
    {
        $tenant = $this->createTenantWithActiveDomain('slot_eng_weekly_closed');
        [$service, $target, $resource] = $this->baseServiceWithResource($tenant);
        AvailabilityRule::query()->create([
            'scheduling_resource_id' => $resource->id,
            'rule_type' => AvailabilityRuleType::WeeklyOpen,
            'weekday' => 1,
            'starts_at_local' => '08:00:00',
            'ends_at_local' => '18:00:00',
            'is_active' => true,
        ]);
        AvailabilityRule::query()->create([
            'scheduling_resource_id' => $resource->id,
            'rule_type' => AvailabilityRuleType::WeeklyClosed,
            'weekday' => 1,
            'starts_at_local' => '12:00:00',
            'ends_at_local' => '13:00:00',
            'is_active' => true,
        ]);

        $this->travelTo(Carbon::parse('2026-05-03 12:00:00', 'UTC'));
        $from = Carbon::parse('2026-05-04 00:00:00', 'UTC')->startOfDay();
        $to = Carbon::parse('2026-05-04 00:00:00', 'UTC')->endOfDay();

        $slots = app(SlotEngineService::class)->slotsForBookableService($service, $from, $to);
        foreach ($slots as $slot) {
            $start = Carbon::parse($slot['starts_at_utc']);
            $this->assertTrue(
                $start->lt(Carbon::parse('2026-05-04 12:00:00', 'UTC'))
                || $start->gte(Carbon::parse('2026-05-04 13:00:00', 'UTC'))
            );
        }
    }
}
