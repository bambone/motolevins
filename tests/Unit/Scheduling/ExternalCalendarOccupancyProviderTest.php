<?php

declare(strict_types=1);

namespace Tests\Unit\Scheduling;

use App\Scheduling\Occupancy\ExternalCalendarOccupancyProvider;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\Support\SchedulingTestScenarios;
use Tests\TestCase;

final class ExternalCalendarOccupancyProviderTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;
    use SchedulingTestScenarios;

    public function test_returns_external_busy_intervals_for_resource(): void
    {
        $tenant = $this->createTenantWithActiveDomain('ext_occ');
        $service = $this->schedulingCreateBookableService($tenant);
        $target = $this->schedulingEnableTargetForService($service);
        $resource = $this->schedulingCreateResource($tenant);
        $this->schedulingAttachResource($target, $resource);

        $this->schedulingExternalBusy(
            $resource,
            Carbon::parse('2026-10-01 12:00:00', 'UTC'),
            Carbon::parse('2026-10-01 13:00:00', 'UTC'),
            false,
        );

        $from = Carbon::parse('2026-10-01 00:00:00', 'UTC');
        $to = Carbon::parse('2026-10-01 23:59:59', 'UTC');
        $intervals = app(ExternalCalendarOccupancyProvider::class)->intervalsFor($resource, $from, $to);

        $this->assertCount(1, $intervals);
        $this->assertFalse($intervals[0]['is_tentative']);
    }

    public function test_marks_tentative_flag(): void
    {
        $tenant = $this->createTenantWithActiveDomain('ext_occ_tent');
        $service = $this->schedulingCreateBookableService($tenant);
        $target = $this->schedulingEnableTargetForService($service);
        $resource = $this->schedulingCreateResource($tenant);
        $this->schedulingAttachResource($target, $resource);

        $this->schedulingExternalBusy(
            $resource,
            Carbon::parse('2026-10-02 12:00:00', 'UTC'),
            Carbon::parse('2026-10-02 13:00:00', 'UTC'),
            true,
        );

        $from = Carbon::parse('2026-10-02 00:00:00', 'UTC');
        $to = Carbon::parse('2026-10-02 23:59:59', 'UTC');
        $intervals = app(ExternalCalendarOccupancyProvider::class)->intervalsFor($resource, $from, $to);

        $this->assertTrue($intervals[0]['is_tentative']);
    }
}
