<?php

declare(strict_types=1);

namespace Tests\Feature\Scheduling;

use App\Models\BookableService;
use App\Models\CalendarConnection;
use App\Models\CalendarSubscription;
use App\Models\PlatformSetting;
use App\Models\SchedulingTarget;
use App\Models\Tenant;
use App\Scheduling\Enums\CalendarAccessMode;
use App\Scheduling\Enums\CalendarProviderType;
use App\Scheduling\Enums\CalendarUsageMode;
use App\Scheduling\Enums\SchedulingScope;
use App\Scheduling\Enums\SchedulingTargetType;
use App\Scheduling\WriteCalendarResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\Support\SchedulingTestScenarios;
use Tests\TestCase;

final class WriteCalendarResolverTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;
    use SchedulingTestScenarios;

    private function activeWriteSubscription(Tenant $tenant): CalendarSubscription
    {
        $conn = CalendarConnection::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'provider' => CalendarProviderType::Google,
            'access_mode' => CalendarAccessMode::Oauth,
            'display_name' => 'W',
            'is_active' => true,
        ]);

        return CalendarSubscription::query()->create([
            'calendar_connection_id' => $conn->id,
            'external_calendar_id' => 'primary',
            'use_for_busy' => true,
            'use_for_write' => true,
            'is_active' => true,
        ]);
    }

    private function writableTarget(BookableService $service): SchedulingTarget
    {
        $target = $service->schedulingTarget;
        $this->assertNotNull($target);
        $target->update([
            'scheduling_enabled' => true,
            'target_type' => SchedulingTargetType::BookableService,
            'target_id' => $service->id,
            'auto_write_to_calendar_enabled' => true,
            'calendar_usage_mode' => CalendarUsageMode::ReadBusyWriteEvents,
        ]);

        return $target->fresh();
    }

    public function test_service_override_wins_over_target_and_tenant(): void
    {
        $tenant = $this->createTenantWithActiveDomain('wr_svc');
        $tenant->update(['calendar_integrations_enabled' => true]);

        $subSvc = $this->activeWriteSubscription($tenant);
        $subTgt = $this->activeWriteSubscription($tenant);
        $subTen = $this->activeWriteSubscription($tenant);

        $service = $this->schedulingCreateBookableService($tenant, [
            'default_write_calendar_subscription_id' => $subSvc->id,
        ]);
        $target = $this->writableTarget($service);
        $target->update(['default_write_calendar_subscription_id' => $subTgt->id]);
        $tenant->update(['scheduling_default_write_calendar_subscription_id' => $subTen->id]);

        $resource = $this->schedulingCreateResource($tenant, [
            'default_write_calendar_subscription_id' => $this->activeWriteSubscription($tenant)->id,
        ]);

        $resolved = app(WriteCalendarResolver::class)->resolveSubscription($service, $target, $resource, $tenant);
        $this->assertNotNull($resolved);
        $this->assertSame($subSvc->id, $resolved->id);
    }

    public function test_target_default_used_when_service_override_null(): void
    {
        $tenant = $this->createTenantWithActiveDomain('wr_tgt');
        $tenant->update(['calendar_integrations_enabled' => true]);

        $subTgt = $this->activeWriteSubscription($tenant);
        $service = $this->schedulingCreateBookableService($tenant);
        $target = $this->writableTarget($service);
        $target->update(['default_write_calendar_subscription_id' => $subTgt->id]);

        $resolved = app(WriteCalendarResolver::class)->resolveSubscription($service, $target, null, $tenant);
        $this->assertNotNull($resolved);
        $this->assertSame($subTgt->id, $resolved->id);
    }

    public function test_resource_default_used_when_service_and_target_null(): void
    {
        $tenant = $this->createTenantWithActiveDomain('wr_res');
        $tenant->update(['calendar_integrations_enabled' => true]);

        $subRes = $this->activeWriteSubscription($tenant);
        $service = $this->schedulingCreateBookableService($tenant);
        $target = $this->writableTarget($service);
        $resource = $this->schedulingCreateResource($tenant, [
            'default_write_calendar_subscription_id' => $subRes->id,
        ]);

        $resolved = app(WriteCalendarResolver::class)->resolveSubscription($service, $target, $resource, $tenant);
        $this->assertNotNull($resolved);
        $this->assertSame($subRes->id, $resolved->id);
    }

    public function test_tenant_default_used_when_no_lower_overrides(): void
    {
        $tenant = $this->createTenantWithActiveDomain('wr_ten');
        $tenant->update(['calendar_integrations_enabled' => true]);

        $subTen = $this->activeWriteSubscription($tenant);
        $service = $this->schedulingCreateBookableService($tenant);
        $target = $this->writableTarget($service);
        $tenant->update(['scheduling_default_write_calendar_subscription_id' => $subTen->id]);

        $resolved = app(WriteCalendarResolver::class)->resolveSubscription($service, $target, null, $tenant);
        $this->assertNotNull($resolved);
        $this->assertSame($subTen->id, $resolved->id);
    }

    public function test_platform_default_used_without_tenant_pointer(): void
    {
        $sub = CalendarSubscription::query()->create([
            'calendar_connection_id' => CalendarConnection::query()->create([
                'scheduling_scope' => SchedulingScope::Tenant,
                'tenant_id' => $this->createTenantWithActiveDomain('wr_plat_conn')->id,
                'provider' => CalendarProviderType::Google,
                'access_mode' => CalendarAccessMode::Oauth,
                'display_name' => 'P',
                'is_active' => true,
            ])->id,
            'external_calendar_id' => 'primary',
            'use_for_busy' => true,
            'use_for_write' => true,
            'is_active' => true,
        ]);

        PlatformSetting::set('scheduling.default_write_calendar_subscription_id', $sub->id, 'integer');

        $resolved = app(WriteCalendarResolver::class)->resolveSubscription(null, null, null, null);
        $this->assertNotNull($resolved);
        $this->assertSame($sub->id, $resolved->id);
    }

    public function test_inactive_service_level_subscription_does_not_fall_through_to_target_default(): void
    {
        $tenant = $this->createTenantWithActiveDomain('wr_bad_svc');
        $tenant->update(['calendar_integrations_enabled' => true]);

        $conn = CalendarConnection::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'provider' => CalendarProviderType::Google,
            'access_mode' => CalendarAccessMode::Oauth,
            'display_name' => 'I',
            'is_active' => true,
        ]);
        $inactiveSub = CalendarSubscription::query()->create([
            'calendar_connection_id' => $conn->id,
            'external_calendar_id' => 'x',
            'use_for_busy' => true,
            'use_for_write' => true,
            'is_active' => false,
        ]);
        $fallback = $this->activeWriteSubscription($tenant);

        $service = $this->schedulingCreateBookableService($tenant, [
            'default_write_calendar_subscription_id' => $inactiveSub->id,
        ]);
        $target = $this->writableTarget($service);
        $target->update(['default_write_calendar_subscription_id' => $fallback->id]);

        $resolved = app(WriteCalendarResolver::class)->resolveSubscription($service, $target, null, $tenant);
        $this->assertNull($resolved);
    }

    public function test_read_busy_only_calendar_mode_forbids_write(): void
    {
        $tenant = $this->createTenantWithActiveDomain('wr_read_busy');
        $tenant->update(['calendar_integrations_enabled' => true]);

        $sub = $this->activeWriteSubscription($tenant);
        $service = $this->schedulingCreateBookableService($tenant);
        $target = $this->writableTarget($service);
        $target->update([
            'calendar_usage_mode' => CalendarUsageMode::ReadBusyOnly,
            'default_write_calendar_subscription_id' => $sub->id,
        ]);

        $resolved = app(WriteCalendarResolver::class)->resolveSubscription($service, $target, null, $tenant);
        $this->assertNull($resolved);
    }

    public function test_auto_write_disabled_returns_null(): void
    {
        $tenant = $this->createTenantWithActiveDomain('wr_no_auto');
        $tenant->update(['calendar_integrations_enabled' => true]);

        $sub = $this->activeWriteSubscription($tenant);
        $service = $this->schedulingCreateBookableService($tenant);
        $target = $this->writableTarget($service);
        $target->update([
            'auto_write_to_calendar_enabled' => false,
            'default_write_calendar_subscription_id' => $sub->id,
        ]);

        $resolved = app(WriteCalendarResolver::class)->resolveSubscription($service, $target, null, $tenant);
        $this->assertNull($resolved);
    }

    public function test_calendar_integrations_entitlement_off_returns_null(): void
    {
        $tenant = $this->createTenantWithActiveDomain('wr_ent_off');
        $tenant->update(['calendar_integrations_enabled' => false]);

        $sub = $this->activeWriteSubscription($tenant);
        $service = $this->schedulingCreateBookableService($tenant);
        $target = $this->writableTarget($service);
        $target->update(['default_write_calendar_subscription_id' => $sub->id]);

        $resolved = app(WriteCalendarResolver::class)->resolveSubscription($service, $target, null, $tenant);
        $this->assertNull($resolved);
    }

    public function test_inactive_subscription_returns_null(): void
    {
        $tenant = $this->createTenantWithActiveDomain('wr_sub_inactive');
        $tenant->update(['calendar_integrations_enabled' => true]);

        $conn = CalendarConnection::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'provider' => CalendarProviderType::Google,
            'access_mode' => CalendarAccessMode::Oauth,
            'display_name' => 'I',
            'is_active' => true,
        ]);
        $sub = CalendarSubscription::query()->create([
            'calendar_connection_id' => $conn->id,
            'external_calendar_id' => 'primary',
            'use_for_busy' => true,
            'use_for_write' => true,
            'is_active' => false,
        ]);

        $service = $this->schedulingCreateBookableService($tenant);
        $target = $this->writableTarget($service);
        $target->update(['default_write_calendar_subscription_id' => $sub->id]);

        $resolved = app(WriteCalendarResolver::class)->resolveSubscription($service, $target, null, $tenant);
        $this->assertNull($resolved);
    }

    public function test_use_for_write_false_returns_null(): void
    {
        $tenant = $this->createTenantWithActiveDomain('wr_no_write_flag');
        $tenant->update(['calendar_integrations_enabled' => true]);

        $conn = CalendarConnection::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'provider' => CalendarProviderType::Google,
            'access_mode' => CalendarAccessMode::Oauth,
            'display_name' => 'NW',
            'is_active' => true,
        ]);
        $sub = CalendarSubscription::query()->create([
            'calendar_connection_id' => $conn->id,
            'external_calendar_id' => 'primary',
            'use_for_busy' => true,
            'use_for_write' => false,
            'is_active' => true,
        ]);

        $service = $this->schedulingCreateBookableService($tenant);
        $target = $this->writableTarget($service);
        $target->update(['default_write_calendar_subscription_id' => $sub->id]);

        $resolved = app(WriteCalendarResolver::class)->resolveSubscription($service, $target, null, $tenant);
        $this->assertNull($resolved);
    }

    public function test_inactive_connection_returns_null(): void
    {
        $tenant = $this->createTenantWithActiveDomain('wr_conn_off');
        $tenant->update(['calendar_integrations_enabled' => true]);

        $conn = CalendarConnection::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'provider' => CalendarProviderType::Google,
            'access_mode' => CalendarAccessMode::Oauth,
            'display_name' => 'Off',
            'is_active' => false,
        ]);
        $sub = CalendarSubscription::query()->create([
            'calendar_connection_id' => $conn->id,
            'external_calendar_id' => 'primary',
            'use_for_busy' => true,
            'use_for_write' => true,
            'is_active' => true,
        ]);

        $service = $this->schedulingCreateBookableService($tenant);
        $target = $this->writableTarget($service);
        $target->update(['default_write_calendar_subscription_id' => $sub->id]);

        $resolved = app(WriteCalendarResolver::class)->resolveSubscription($service, $target, null, $tenant);
        $this->assertNull($resolved);
    }
}
