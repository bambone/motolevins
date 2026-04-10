<?php

declare(strict_types=1);

namespace Tests\Feature\Scheduling\Rental;

use App\Models\ExternalBusyBlock;
use App\Models\Motorcycle;
use App\Models\RentalUnit;
use App\Models\SchedulingResource;
use App\Models\SchedulingTarget;
use App\Scheduling\Enums\AssignmentStrategy;
use App\Scheduling\Enums\ExternalBusyEffect;
use App\Scheduling\Enums\SchedulingResourceType;
use App\Scheduling\Enums\SchedulingScope;
use App\Scheduling\Enums\SchedulingTargetType;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

final class RentalSchedulingWarningsTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    private function rentalWithSchedulingTarget(ExternalBusyEffect $effect): array
    {
        $tenant = $this->createTenantWithActiveDomain('rent_warn_'.$effect->value);
        $m = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Bike',
            'slug' => 'bike-w-'.$effect->value,
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
            'label' => 'Res',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        $target = SchedulingTarget::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'target_type' => SchedulingTargetType::RentalUnit,
            'target_id' => $unit->id,
            'label' => 'Rental '.$unit->id,
            'scheduling_enabled' => false,
            'external_busy_enabled' => true,
            'internal_busy_enabled' => true,
            'auto_write_to_calendar_enabled' => false,
            'external_busy_effect' => $effect,
            'is_active' => true,
        ]);

        $target->schedulingResources()->attach($resource->id, [
            'priority' => 0,
            'is_default' => true,
            'assignment_strategy' => AssignmentStrategy::FirstAvailable->value,
        ]);

        ExternalBusyBlock::query()->create([
            'scheduling_resource_id' => $resource->id,
            'scheduling_target_id' => $target->id,
            'calendar_subscription_id' => null,
            'starts_at_utc' => Carbon::parse('2026-07-01 10:00:00', 'UTC'),
            'ends_at_utc' => Carbon::parse('2026-07-01 14:00:00', 'UTC'),
            'source_event_id' => 'sw-1',
            'is_tentative' => false,
            'raw_payload' => null,
        ]);

        return [$unit, app(AvailabilityService::class)];
    }

    public function test_soft_warning_is_available_but_surfaces_warnings_and_not_hard_conflicts(): void
    {
        [$unit, $svc] = $this->rentalWithSchedulingTarget(ExternalBusyEffect::SoftWarning);
        $start = Carbon::parse('2026-07-01 11:00:00', 'UTC');
        $end = Carbon::parse('2026-07-01 12:00:00', 'UTC');

        $this->assertTrue($svc->isAvailable($unit, $start, $end));
        $this->assertTrue($svc->getConflicts($unit, $start, $end)->isEmpty());
        $warnings = $svc->getExternalSchedulingSoftWarnings($unit, $start, $end);
        $this->assertCount(1, $warnings);
        $this->assertSame('warning', $warnings->first()->status);
        $this->assertSame('external_calendar_soft', $warnings->first()->source);
    }
}
