<?php

declare(strict_types=1);

namespace Tests\Feature\Scheduling;

use App\Models\CalendarConnection;
use App\Models\CalendarOccupancyMapping;
use App\Models\CalendarSubscription;
use App\Scheduling\Enums\CalendarAccessMode;
use App\Scheduling\Enums\CalendarProviderType;
use App\Scheduling\Enums\CalendarUsageMode;
use App\Scheduling\Enums\IntegrationErrorPolicy;
use App\Scheduling\Enums\OccupancyMappingType;
use App\Scheduling\Enums\SchedulingScope;
use App\Scheduling\Enums\StaleBusyPolicy;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\Support\SchedulingTestScenarios;
use Tests\TestCase;

final class SchedulingIntegrationPoliciesTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;
    use SchedulingTestScenarios;

    public function test_slots_api_returns_warning_when_integration_error_policy_warn_only(): void
    {
        $tenant = $this->createTenantWithActiveDomain('pol_warn_slots');
        $tenant->update(['scheduling_integration_error_policy' => IntegrationErrorPolicy::WarnOnly]);
        CalendarConnection::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'provider' => CalendarProviderType::Google,
            'access_mode' => CalendarAccessMode::Oauth,
            'display_name' => 'E',
            'is_active' => true,
            'last_error' => 'temporary',
        ]);

        $service = $this->schedulingCreateBookableService($tenant);
        $target = $this->schedulingEnableTargetForService($service);
        $resource = $this->schedulingCreateResource($tenant);
        $this->schedulingAttachResource($target, $resource);
        $this->schedulingWeeklyOpenRule($resource, 1, '10:00:00', '12:00:00');

        $host = $this->tenancyHostForSlug('pol_warn_slots');
        $this->travelTo(Carbon::parse('2026-05-03 12:00:00', 'UTC'));

        $response = $this->getJson('http://'.$host.'/api/tenant/scheduling/bookable-services/'.$service->id.'/slots?from=2026-05-04&to=2026-05-04');
        $response->assertOk();
        $response->assertJsonFragment(['scheduling_calendar_integration_error']);
        $this->assertNotEmpty($response->json('slots'));
    }

    public function test_slots_api_returns_stale_warning_when_warn_only_stale(): void
    {
        $tenant = $this->createTenantWithActiveDomain('pol_stale_slots');
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

        $host = $this->tenancyHostForSlug('pol_stale_slots');
        $this->travelTo(Carbon::parse('2026-05-03 12:00:00', 'UTC'));

        $response = $this->getJson('http://'.$host.'/api/tenant/scheduling/bookable-services/'.$service->id.'/slots?from=2026-05-04&to=2026-05-04');
        $response->assertOk();
        $response->assertJsonFragment(['scheduling_external_busy_stale']);
    }

    public function test_slots_api_empty_when_integration_blocks(): void
    {
        $tenant = $this->createTenantWithActiveDomain('pol_block_slots');
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

        $host = $this->tenancyHostForSlug('pol_block_slots');
        $this->travelTo(Carbon::parse('2026-05-03 12:00:00', 'UTC'));

        $response = $this->getJson('http://'.$host.'/api/tenant/scheduling/bookable-services/'.$service->id.'/slots?from=2026-05-04&to=2026-05-04');
        $response->assertOk();
        $this->assertSame([], $response->json('slots'));
    }
}
