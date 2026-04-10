<?php

declare(strict_types=1);

namespace Tests\Feature\Scheduling;

use App\Models\AppointmentHold;
use App\Models\CalendarConnection;
use App\Models\CalendarOccupancyMapping;
use App\Models\CalendarSubscription;
use App\Models\CrmRequest;
use App\Scheduling\AppointmentInboundService;
use App\Scheduling\Enums\AppointmentHoldStatus;
use App\Scheduling\Enums\CalendarAccessMode;
use App\Scheduling\Enums\CalendarProviderType;
use App\Scheduling\Enums\CalendarUsageMode;
use App\Scheduling\Enums\IntegrationErrorPolicy;
use App\Scheduling\Enums\OccupancyMappingType;
use App\Scheduling\Enums\SchedulingScope;
use App\Scheduling\Enums\StaleBusyPolicy;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\Support\SchedulingTestScenarios;
use Tests\TestCase;

final class AppointmentInboundServiceTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;
    use SchedulingTestScenarios;

    public function test_second_hold_on_same_slot_fails(): void
    {
        $tenant = $this->createTenantWithActiveDomain('appt_hold_race');
        $service = $this->schedulingCreateBookableService($tenant);
        $target = $this->schedulingEnableTargetForService($service);
        $resource = $this->schedulingCreateResource($tenant);
        $this->schedulingAttachResource($target, $resource);
        $this->schedulingWeeklyOpenRule($resource, 1, '10:00:00', '12:00:00');

        $this->travelTo(Carbon::parse('2026-05-03 12:00:00', 'UTC'));
        $start = Carbon::parse('2026-05-04 10:00:00', 'UTC');
        $end = Carbon::parse('2026-05-04 10:30:00', 'UTC');

        $inbound = app(AppointmentInboundService::class);
        $h1 = $inbound->createHold($tenant, $service, $resource->id, $start, $end);
        $this->assertInstanceOf(AppointmentHold::class, $h1);

        try {
            $inbound->createHold($tenant, $service, $resource->id, $start, $end);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('slot', $e->errors());
        }
    }

    public function test_submit_hold_creates_crm_request_and_links_payload(): void
    {
        $tenant = $this->createTenantWithActiveDomain('appt_submit');
        $service = $this->schedulingCreateBookableService($tenant, ['requires_confirmation' => false]);
        $target = $this->schedulingEnableTargetForService($service);
        $resource = $this->schedulingCreateResource($tenant);
        $this->schedulingAttachResource($target, $resource);
        $this->schedulingWeeklyOpenRule($resource, 1, '10:00:00', '12:00:00');

        $this->travelTo(Carbon::parse('2026-05-03 12:00:00', 'UTC'));
        $start = Carbon::parse('2026-05-04 10:00:00', 'UTC');
        $end = Carbon::parse('2026-05-04 10:30:00', 'UTC');

        $inbound = app(AppointmentInboundService::class);
        $hold = $inbound->createHold($tenant, $service, $resource->id, $start, $end);

        $crm = $inbound->submitHold($tenant, $hold, 'Иван', '+70000000000', 'a@b.test', 'Нужна консультация по аренде');

        $this->assertInstanceOf(CrmRequest::class, $crm);
        $hold->refresh();
        $this->assertSame($crm->id, $hold->crm_request_id);
        $this->assertSame(AppointmentHoldStatus::Confirmed, $hold->status);

        $payload = $crm->payload_json;
        $this->assertIsArray($payload);
        $this->assertSame($service->id, $payload['bookable_service_id']);
        $this->assertSame($target->id, $payload['scheduling_target_id']);
        $this->assertSame($hold->id, $payload['appointment_hold_id']);
        $this->assertSame('public', $payload['slot_source']);
    }

    public function test_requires_confirmation_sets_hold_pending(): void
    {
        $tenant = $this->createTenantWithActiveDomain('appt_pending');
        $service = $this->schedulingCreateBookableService($tenant, ['requires_confirmation' => true]);
        $target = $this->schedulingEnableTargetForService($service);
        $resource = $this->schedulingCreateResource($tenant);
        $this->schedulingAttachResource($target, $resource);
        $this->schedulingWeeklyOpenRule($resource, 1, '10:00:00', '12:00:00');

        $this->travelTo(Carbon::parse('2026-05-03 12:00:00', 'UTC'));
        $start = Carbon::parse('2026-05-04 10:00:00', 'UTC');
        $end = Carbon::parse('2026-05-04 10:30:00', 'UTC');

        $inbound = app(AppointmentInboundService::class);
        $hold = $inbound->createHold($tenant, $service, $resource->id, $start, $end);
        $inbound->submitHold($tenant, $hold, 'Иван', '+70000000000', 'pending@test.example', 'Сообщение достаточной длины');

        $hold->refresh();
        $this->assertSame(AppointmentHoldStatus::Pending, $hold->status);
    }

    public function test_expired_hold_cannot_be_submitted(): void
    {
        $tenant = $this->createTenantWithActiveDomain('appt_exp');
        $service = $this->schedulingCreateBookableService($tenant);
        $target = $this->schedulingEnableTargetForService($service);
        $resource = $this->schedulingCreateResource($tenant);
        $this->schedulingAttachResource($target, $resource);
        $this->schedulingWeeklyOpenRule($resource, 1, '10:00:00', '12:00:00');

        $this->travelTo(Carbon::parse('2026-05-03 12:00:00', 'UTC'));
        $hold = AppointmentHold::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'bookable_service_id' => $service->id,
            'scheduling_resource_id' => $resource->id,
            'starts_at_utc' => Carbon::parse('2026-05-04 10:00:00', 'UTC'),
            'ends_at_utc' => Carbon::parse('2026-05-04 10:30:00', 'UTC'),
            'status' => AppointmentHoldStatus::Hold,
            'source' => 'test',
            'expires_at' => Carbon::parse('2026-05-03 11:00:00', 'UTC'),
        ]);

        $this->expectException(ValidationException::class);
        app(AppointmentInboundService::class)->submitHold(
            $tenant,
            $hold,
            'Иван',
            null,
            null,
            'Сообщение достаточной длины',
        );
    }

    public function test_create_hold_blocked_when_integration_error_policy_blocks(): void
    {
        $tenant = $this->createTenantWithActiveDomain('appt_int_block');
        $tenant->update(['scheduling_integration_error_policy' => IntegrationErrorPolicy::BlockScheduling]);
        CalendarConnection::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'provider' => CalendarProviderType::Google,
            'access_mode' => CalendarAccessMode::Oauth,
            'display_name' => 'X',
            'is_active' => true,
            'last_error' => 'boom',
        ]);

        $service = $this->schedulingCreateBookableService($tenant);
        $target = $this->schedulingEnableTargetForService($service);
        $resource = $this->schedulingCreateResource($tenant);
        $this->schedulingAttachResource($target, $resource);
        $this->schedulingWeeklyOpenRule($resource, 1, '10:00:00', '12:00:00');

        $this->travelTo(Carbon::parse('2026-05-03 12:00:00', 'UTC'));
        $this->expectException(ValidationException::class);
        app(AppointmentInboundService::class)->createHold(
            $tenant,
            $service,
            $resource->id,
            Carbon::parse('2026-05-04 10:00:00', 'UTC'),
            Carbon::parse('2026-05-04 10:30:00', 'UTC'),
        );
    }

    public function test_create_hold_allowed_when_integration_policy_warn_only(): void
    {
        $tenant = $this->createTenantWithActiveDomain('appt_int_warn');
        $tenant->update(['scheduling_integration_error_policy' => IntegrationErrorPolicy::WarnOnly]);
        CalendarConnection::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'provider' => CalendarProviderType::Google,
            'access_mode' => CalendarAccessMode::Oauth,
            'display_name' => 'X',
            'is_active' => true,
            'last_error' => 'boom',
        ]);

        $service = $this->schedulingCreateBookableService($tenant);
        $target = $this->schedulingEnableTargetForService($service);
        $resource = $this->schedulingCreateResource($tenant);
        $this->schedulingAttachResource($target, $resource);
        $this->schedulingWeeklyOpenRule($resource, 1, '10:00:00', '12:00:00');

        $this->travelTo(Carbon::parse('2026-05-03 12:00:00', 'UTC'));
        $hold = app(AppointmentInboundService::class)->createHold(
            $tenant,
            $service,
            $resource->id,
            Carbon::parse('2026-05-04 10:00:00', 'UTC'),
            Carbon::parse('2026-05-04 10:30:00', 'UTC'),
        );
        $this->assertSame(AppointmentHoldStatus::Hold, $hold->status);
    }

    public function test_create_hold_blocked_when_stale_policy_blocks_and_data_stale(): void
    {
        $tenant = $this->createTenantWithActiveDomain('appt_stale_block');
        $tenant->update(['scheduling_stale_busy_policy' => StaleBusyPolicy::BlockNewSlots]);

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
            'display_name' => 'S',
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
            'is_active' => true,
        ]);

        $this->travelTo(Carbon::parse('2026-05-03 12:00:00', 'UTC'));
        $this->expectException(ValidationException::class);
        app(AppointmentInboundService::class)->createHold(
            $tenant,
            $service,
            $resource->id,
            Carbon::parse('2026-05-04 10:00:00', 'UTC'),
            Carbon::parse('2026-05-04 10:30:00', 'UTC'),
        );
    }

    public function test_scheduling_disabled_entitlement_blocks_hold(): void
    {
        $tenant = $this->createTenantWithActiveDomain('appt_no_ent');
        $tenant->update(['scheduling_module_enabled' => false]);

        $service = $this->schedulingCreateBookableService($tenant);
        $target = $this->schedulingEnableTargetForService($service);
        $resource = $this->schedulingCreateResource($tenant);
        $this->schedulingAttachResource($target, $resource);
        $this->schedulingWeeklyOpenRule($resource, 1, '10:00:00', '12:00:00');

        $this->travelTo(Carbon::parse('2026-05-03 12:00:00', 'UTC'));
        $this->expectException(ValidationException::class);
        app(AppointmentInboundService::class)->createHold(
            $tenant,
            $service,
            $resource->id,
            Carbon::parse('2026-05-04 10:00:00', 'UTC'),
            Carbon::parse('2026-05-04 10:30:00', 'UTC'),
        );
    }
}
