<?php

declare(strict_types=1);

namespace Tests\Unit\Scheduling;

use App\Models\AppointmentHold;
use App\Scheduling\Enums\AppointmentHoldStatus;
use App\Scheduling\Enums\SchedulingScope;
use App\Scheduling\Occupancy\InternalSchedulingOccupancyProvider;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\Support\SchedulingTestScenarios;
use Tests\TestCase;

final class InternalSchedulingOccupancyProviderTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;
    use SchedulingTestScenarios;

    public function test_includes_manual_busy_and_all_overlapping_holds(): void
    {
        $tenant = $this->createTenantWithActiveDomain('int_occ');
        $service = $this->schedulingCreateBookableService($tenant);
        $target = $this->schedulingEnableTargetForService($service);
        $resource = $this->schedulingCreateResource($tenant);
        $this->schedulingAttachResource($target, $resource);

        $this->schedulingManualBusy(
            $tenant,
            $resource,
            Carbon::parse('2026-09-01 09:00:00', 'UTC'),
            Carbon::parse('2026-09-01 10:00:00', 'UTC'),
            $target,
        );

        AppointmentHold::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'bookable_service_id' => $service->id,
            'scheduling_resource_id' => $resource->id,
            'starts_at_utc' => Carbon::parse('2026-09-01 11:00:00', 'UTC'),
            'ends_at_utc' => Carbon::parse('2026-09-01 11:30:00', 'UTC'),
            'status' => AppointmentHoldStatus::Hold,
            'source' => 'test',
            'expires_at' => Carbon::parse('2026-09-02 00:00:00', 'UTC'),
        ]);

        $from = Carbon::parse('2026-09-01 08:00:00', 'UTC');
        $to = Carbon::parse('2026-09-01 18:00:00', 'UTC');
        $intervals = app(InternalSchedulingOccupancyProvider::class)->intervalsFor($resource, $target, $from, $to);

        $this->assertCount(2, $intervals);
    }
}
