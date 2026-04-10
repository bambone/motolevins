<?php

declare(strict_types=1);

namespace Tests\Feature\Scheduling;

use App\Models\CalendarConnection;
use App\Models\CalendarOccupancyMapping;
use App\Models\CalendarSubscription;
use App\Models\ExternalBusyBlock;
use App\Models\Motorcycle;
use App\Models\PlatformSetting;
use App\Models\RentalUnit;
use App\Models\SchedulingResource;
use App\Models\SchedulingTarget;
use App\Scheduling\Enums\AssignmentStrategy;
use App\Scheduling\Enums\CalendarAccessMode;
use App\Scheduling\Enums\CalendarProviderType;
use App\Scheduling\Enums\CalendarUsageMode;
use App\Scheduling\Enums\ExternalBusyEffect;
use App\Scheduling\Enums\IntegrationErrorPolicy;
use App\Scheduling\Enums\MatchConfidence;
use App\Scheduling\Enums\MatchMode;
use App\Scheduling\Enums\OccupancyMappingType;
use App\Scheduling\Enums\SchedulingResourceType;
use App\Scheduling\Enums\SchedulingScope;
use App\Scheduling\Enums\SchedulingTargetType;
use App\Scheduling\Enums\StaleBusyPolicy;
use App\Scheduling\SlotEngineService;
use App\Scheduling\WriteCalendarResolver;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\Support\SchedulingTestScenarios;
use Tests\TestCase;

/**
 * Продуктовые «сквозные» сценарии: читаемые имена = живая спецификация модуля.
 */
final class SchedulingGoldenScenariosTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;
    use SchedulingTestScenarios;

    public function test_scenario_morning_consultation_slots_remain_available_when_external_busy_is_afternoon_only(): void
    {
        $tenant = $this->createTenantWithActiveDomain('gold_morning');
        $service = $this->schedulingCreateBookableService($tenant, ['duration_minutes' => 30, 'slot_step_minutes' => 30]);
        $target = $this->schedulingEnableTargetForService($service, [
            'external_busy_enabled' => true,
            'calendar_usage_mode' => CalendarUsageMode::ReadBusyOnly,
        ]);
        $resource = $this->schedulingCreateResource($tenant, ['timezone' => 'UTC']);
        $this->schedulingAttachResource($target, $resource);
        $this->schedulingWeeklyOpenRule($resource, 1, '09:00:00', '12:00:00');

        // 2026-11-30 is Monday (ISO weekday 1) — aligns with weekly open rule.
        $this->schedulingExternalBusy(
            $resource,
            Carbon::parse('2026-11-30 14:00:00', 'UTC'),
            Carbon::parse('2026-11-30 17:00:00', 'UTC'),
        );

        $this->travelTo(Carbon::parse('2026-11-29 12:00:00', 'UTC'));
        $from = Carbon::parse('2026-11-30 00:00:00', 'UTC')->startOfDay();
        $to = Carbon::parse('2026-11-30 00:00:00', 'UTC')->endOfDay();

        $slots = app(SlotEngineService::class)->slotsForBookableService($service, $from, $to);
        $this->assertNotEmpty($slots);
        foreach ($slots as $slot) {
            $start = Carbon::parse($slot['starts_at_utc']);
            $this->assertTrue($start->lt(Carbon::parse('2026-11-30 14:00:00', 'UTC')));
        }
    }

    public function test_scenario_rental_unit_hard_block_treats_overlapping_external_busy_as_conflict(): void
    {
        $tenant = $this->createTenantWithActiveDomain('gold_rent_hard');
        $m = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'M',
            'slug' => 'm-gold-h',
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
            'label' => 'U',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);
        $tgt = SchedulingTarget::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'target_type' => SchedulingTargetType::RentalUnit,
            'target_id' => $unit->id,
            'label' => 'RU',
            'scheduling_enabled' => false,
            'external_busy_enabled' => true,
            'internal_busy_enabled' => true,
            'auto_write_to_calendar_enabled' => false,
            'external_busy_effect' => ExternalBusyEffect::HardBlock,
            'is_active' => true,
        ]);
        $tgt->schedulingResources()->attach($resource->id, [
            'priority' => 0,
            'is_default' => true,
            'assignment_strategy' => AssignmentStrategy::FirstAvailable->value,
        ]);
        ExternalBusyBlock::query()->create([
            'scheduling_resource_id' => $resource->id,
            'scheduling_target_id' => $tgt->id,
            'starts_at_utc' => Carbon::parse('2026-12-10 10:00:00', 'UTC'),
            'ends_at_utc' => Carbon::parse('2026-12-10 18:00:00', 'UTC'),
            'source_event_id' => 'g1',
            'is_tentative' => false,
        ]);

        $start = Carbon::parse('2026-12-10 12:00:00', 'UTC');
        $end = Carbon::parse('2026-12-10 13:00:00', 'UTC');
        $this->assertFalse(app(AvailabilityService::class)->isAvailable($unit, $start, $end));
    }

    public function test_scenario_rental_unit_soft_warning_keeps_availability_but_surfaces_warning_collection(): void
    {
        $tenant = $this->createTenantWithActiveDomain('gold_rent_soft');
        $m = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'M2',
            'slug' => 'm-gold-s',
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
            'label' => 'U2',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);
        $tgt = SchedulingTarget::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'target_type' => SchedulingTargetType::RentalUnit,
            'target_id' => $unit->id,
            'label' => 'RU2',
            'scheduling_enabled' => false,
            'external_busy_enabled' => true,
            'internal_busy_enabled' => true,
            'auto_write_to_calendar_enabled' => false,
            'external_busy_effect' => ExternalBusyEffect::SoftWarning,
            'is_active' => true,
        ]);
        $tgt->schedulingResources()->attach($resource->id, [
            'priority' => 0,
            'is_default' => true,
            'assignment_strategy' => AssignmentStrategy::FirstAvailable->value,
        ]);
        ExternalBusyBlock::query()->create([
            'scheduling_resource_id' => $resource->id,
            'scheduling_target_id' => $tgt->id,
            'starts_at_utc' => Carbon::parse('2026-12-11 10:00:00', 'UTC'),
            'ends_at_utc' => Carbon::parse('2026-12-11 18:00:00', 'UTC'),
            'source_event_id' => 'g2',
            'is_tentative' => false,
        ]);

        $start = Carbon::parse('2026-12-11 12:00:00', 'UTC');
        $end = Carbon::parse('2026-12-11 13:00:00', 'UTC');
        $svc = app(AvailabilityService::class);
        $this->assertTrue($svc->isAvailable($unit, $start, $end));
        $this->assertCount(1, $svc->getExternalSchedulingSoftWarnings($unit, $start, $end));
    }

    public function test_scenario_public_slots_api_warns_on_stale_busy_when_policy_is_warn_only(): void
    {
        $tenant = $this->createTenantWithActiveDomain('gold_stale_warn');
        $tenant->update(['scheduling_stale_busy_policy' => StaleBusyPolicy::WarnOnly]);

        $service = $this->schedulingCreateBookableService($tenant);
        $target = $this->schedulingEnableTargetForService($service, [
            'external_busy_enabled' => true,
            'calendar_usage_mode' => CalendarUsageMode::ReadBusyOnly,
        ]);
        $resource = $this->schedulingCreateResource($tenant);
        $this->schedulingAttachResource($target, $resource);
        $this->schedulingWeeklyOpenRule($resource, 1, '10:00:00', '12:00:00');

        $conn = CalendarConnection::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'provider' => CalendarProviderType::Google,
            'access_mode' => CalendarAccessMode::Oauth,
            'display_name' => 'G',
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
            'match_mode' => MatchMode::EntireCalendar,
            'match_confidence' => MatchConfidence::High,
            'is_active' => true,
        ]);

        // 2026-12-07 is Monday — matches weekday 1 open rule.
        $this->travelTo(Carbon::parse('2026-12-06 12:00:00', 'UTC'));
        $host = $this->tenancyHostForSlug('gold_stale_warn');
        $response = $this->getJson('http://'.$host.'/api/tenant/scheduling/bookable-services/'.$service->id.'/slots?from=2026-12-07&to=2026-12-07');

        $response->assertOk();
        $response->assertJsonFragment(['scheduling_external_busy_stale']);
        $this->assertNotEmpty($response->json('slots'));
    }

    public function test_scenario_public_slots_empty_when_only_manual_after_request_resources_in_pool(): void
    {
        $tenant = $this->createTenantWithActiveDomain('gold_man_req');
        $service = $this->schedulingCreateBookableService($tenant);
        $target = $this->schedulingEnableTargetForService($service);
        $resource = $this->schedulingCreateResource($tenant);
        $this->schedulingAttachResource($target, $resource, [
            'assignment_strategy' => AssignmentStrategy::ManualAfterRequest,
        ]);
        $this->schedulingWeeklyOpenRule($resource, 1, '10:00:00', '12:00:00');

        $this->travelTo(Carbon::parse('2026-12-06 12:00:00', 'UTC'));
        $host = $this->tenancyHostForSlug('gold_man_req');
        $response = $this->getJson('http://'.$host.'/api/tenant/scheduling/bookable-services/'.$service->id.'/slots?from=2026-12-07&to=2026-12-07');

        $response->assertOk();
        $this->assertSame([], $response->json('slots'));
    }

    public function test_scenario_write_resolver_falls_through_tenant_default_to_platform_setting(): void
    {
        $tenantA = $this->createTenantWithActiveDomain('gold_res_plat_a');
        $tenantConn = $this->createTenantWithActiveDomain('gold_res_plat_conn');

        $conn = CalendarConnection::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenantConn->id,
            'provider' => CalendarProviderType::Google,
            'access_mode' => CalendarAccessMode::Oauth,
            'display_name' => 'P',
            'is_active' => true,
        ]);
        $sub = CalendarSubscription::query()->create([
            'calendar_connection_id' => $conn->id,
            'external_calendar_id' => 'primary',
            'use_for_busy' => true,
            'use_for_write' => true,
            'is_active' => true,
        ]);

        PlatformSetting::query()->where('key', 'scheduling.default_write_calendar_subscription_id')->delete();
        Cache::forget('platform_settings.scheduling.default_write_calendar_subscription_id');

        $tenantA->update(['calendar_integrations_enabled' => true]);
        $service = $this->schedulingCreateBookableService($tenantA);
        $target = $service->schedulingTarget;
        $this->assertNotNull($target);
        $target->update([
            'scheduling_enabled' => true,
            'target_type' => SchedulingTargetType::BookableService,
            'target_id' => $service->id,
            'auto_write_to_calendar_enabled' => true,
            'calendar_usage_mode' => CalendarUsageMode::ReadBusyWriteEvents,
        ]);

        $this->assertNull(app(WriteCalendarResolver::class)->resolveSubscription($service, $target, null, $tenantA));

        $tenantA->update(['scheduling_default_write_calendar_subscription_id' => $sub->id]);
        $this->assertSame(
            $sub->id,
            app(WriteCalendarResolver::class)->resolveSubscription($service->fresh(), $target->fresh(), null, $tenantA)?->id,
        );

        $tenantA->update(['scheduling_default_write_calendar_subscription_id' => null]);
        PlatformSetting::set('scheduling.default_write_calendar_subscription_id', $sub->id, 'integer');
        $this->assertSame(
            $sub->id,
            app(WriteCalendarResolver::class)->resolveSubscription($service->fresh(), $target->fresh(), null, $tenantA)?->id,
        );
    }

    public function test_scenario_integration_error_blocks_public_slot_list(): void
    {
        $tenant = $this->createTenantWithActiveDomain('gold_int_block');
        $tenant->update(['scheduling_integration_error_policy' => IntegrationErrorPolicy::BlockScheduling]);
        CalendarConnection::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'provider' => CalendarProviderType::Google,
            'access_mode' => CalendarAccessMode::Oauth,
            'display_name' => 'E',
            'is_active' => true,
            'last_error' => 'down',
        ]);

        $service = $this->schedulingCreateBookableService($tenant);
        $target = $this->schedulingEnableTargetForService($service);
        $resource = $this->schedulingCreateResource($tenant);
        $this->schedulingAttachResource($target, $resource);
        $this->schedulingWeeklyOpenRule($resource, 1, '10:00:00', '12:00:00');

        $this->travelTo(Carbon::parse('2026-12-06 12:00:00', 'UTC'));
        $host = $this->tenancyHostForSlug('gold_int_block');
        $response = $this->getJson('http://'.$host.'/api/tenant/scheduling/bookable-services/'.$service->id.'/slots?from=2026-12-07&to=2026-12-07');

        $response->assertOk();
        $this->assertSame([], $response->json('slots'));
    }
}
