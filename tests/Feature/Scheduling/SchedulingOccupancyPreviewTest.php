<?php

declare(strict_types=1);

namespace Tests\Feature\Scheduling;

use App\Models\AppointmentHold;
use App\Scheduling\Enums\AppointmentHoldStatus;
use App\Scheduling\Enums\SchedulingScope;
use App\Scheduling\Occupancy\SchedulingOccupancyPreviewService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\Support\SchedulingTestScenarios;
use Tests\TestCase;

/**
 * {@see SchedulingOccupancyPreviewService}: internal vs external buckets (diagnostic preview, not slot picker).
 *
 * Stale / soft severity for rental are covered elsewhere; preview currently does not attach stale metadata to rows.
 */
final class SchedulingOccupancyPreviewTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;
    use SchedulingTestScenarios;

    public function test_internal_bucket_empty_when_no_target_narrowing_even_if_manual_busy_exists(): void
    {
        $tenant = $this->createTenantWithActiveDomain('occ_prev_narrow');
        $service = $this->schedulingCreateBookableService($tenant);
        $target = $this->schedulingEnableTargetForService($service);
        $resource = $this->schedulingCreateResource($tenant);
        $this->schedulingAttachResource($target, $resource);

        $this->schedulingManualBusy(
            $tenant,
            $resource,
            Carbon::parse('2026-11-01 10:00:00', 'UTC'),
            Carbon::parse('2026-11-01 11:00:00', 'UTC'),
            $target,
        );

        $from = Carbon::parse('2026-11-01 00:00:00', 'UTC');
        $to = Carbon::parse('2026-11-01 23:59:59', 'UTC');

        $payload = app(SchedulingOccupancyPreviewService::class)->previewForResource($tenant, $resource, $from, $to, null);

        $this->assertSame([], $payload['internal']);
    }

    public function test_internal_includes_manual_busy_when_target_matches_narrowing(): void
    {
        $tenant = $this->createTenantWithActiveDomain('occ_prev_int');
        $service = $this->schedulingCreateBookableService($tenant);
        $target = $this->schedulingEnableTargetForService($service);
        $resource = $this->schedulingCreateResource($tenant);
        $this->schedulingAttachResource($target, $resource);

        $this->schedulingManualBusy(
            $tenant,
            $resource,
            Carbon::parse('2026-11-02 10:00:00', 'UTC'),
            Carbon::parse('2026-11-02 11:00:00', 'UTC'),
            $target,
        );

        $from = Carbon::parse('2026-11-02 00:00:00', 'UTC');
        $to = Carbon::parse('2026-11-02 23:59:59', 'UTC');

        $payload = app(SchedulingOccupancyPreviewService::class)->previewForResource($tenant, $resource, $from, $to, $target);

        $this->assertCount(1, $payload['internal']);
        $this->assertStringContainsString('10:00:00', $payload['internal'][0]['start']);
    }

    public function test_internal_includes_holds_when_target_narrowed(): void
    {
        $tenant = $this->createTenantWithActiveDomain('occ_prev_hold');
        $service = $this->schedulingCreateBookableService($tenant);
        $target = $this->schedulingEnableTargetForService($service);
        $resource = $this->schedulingCreateResource($tenant);
        $this->schedulingAttachResource($target, $resource);

        AppointmentHold::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'bookable_service_id' => $service->id,
            'scheduling_resource_id' => $resource->id,
            'starts_at_utc' => Carbon::parse('2026-11-03 14:00:00', 'UTC'),
            'ends_at_utc' => Carbon::parse('2026-11-03 14:30:00', 'UTC'),
            'status' => AppointmentHoldStatus::Hold,
            'source' => 'test',
            'expires_at' => Carbon::parse('2026-11-04 00:00:00', 'UTC'),
        ]);

        $from = Carbon::parse('2026-11-03 00:00:00', 'UTC');
        $to = Carbon::parse('2026-11-03 23:59:59', 'UTC');

        $payload = app(SchedulingOccupancyPreviewService::class)->previewForResource($tenant, $resource, $from, $to, $target);

        $this->assertGreaterThanOrEqual(1, count($payload['internal']));
    }

    public function test_external_bucket_lists_blocks_with_tentative_flag(): void
    {
        $tenant = $this->createTenantWithActiveDomain('occ_prev_ext');
        $service = $this->schedulingCreateBookableService($tenant);
        $target = $this->schedulingEnableTargetForService($service);
        $resource = $this->schedulingCreateResource($tenant);
        $this->schedulingAttachResource($target, $resource);

        $this->schedulingExternalBusy(
            $resource,
            Carbon::parse('2026-11-04 09:00:00', 'UTC'),
            Carbon::parse('2026-11-04 10:00:00', 'UTC'),
            true,
        );

        $from = Carbon::parse('2026-11-04 00:00:00', 'UTC');
        $to = Carbon::parse('2026-11-04 23:59:59', 'UTC');

        $payload = app(SchedulingOccupancyPreviewService::class)->previewForResource($tenant, $resource, $from, $to, null);

        $this->assertCount(1, $payload['external']);
        $this->assertTrue($payload['external'][0]['is_tentative']);
    }

    public function test_wrong_tenant_resource_aborts_forbidden(): void
    {
        $tenantA = $this->createTenantWithActiveDomain('occ_prev_a');
        $tenantB = $this->createTenantWithActiveDomain('occ_prev_b');

        $serviceB = $this->schedulingCreateBookableService($tenantB);
        $targetB = $this->schedulingEnableTargetForService($serviceB);
        $resourceB = $this->schedulingCreateResource($tenantB);
        $this->schedulingAttachResource($targetB, $resourceB);

        $from = Carbon::parse('2026-11-05 00:00:00', 'UTC');
        $to = Carbon::parse('2026-11-05 23:59:59', 'UTC');

        try {
            app(SchedulingOccupancyPreviewService::class)->previewForResource($tenantA, $resourceB, $from, $to, null);
            $this->fail('Expected HttpException 403');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }
}
