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

final class RentalExternalBusyDisabledTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    public function test_external_busy_ignored_when_external_busy_disabled_even_with_hard_block_effect(): void
    {
        $tenant = $this->createTenantWithActiveDomain('rent_ext_off');
        $m = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Bike',
            'slug' => 'bike-off',
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
            'external_busy_enabled' => false,
            'internal_busy_enabled' => true,
            'auto_write_to_calendar_enabled' => false,
            'external_busy_effect' => ExternalBusyEffect::HardBlock,
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
            'starts_at_utc' => Carbon::parse('2026-08-01 10:00:00', 'UTC'),
            'ends_at_utc' => Carbon::parse('2026-08-01 14:00:00', 'UTC'),
            'source_event_id' => 'off-1',
            'is_tentative' => false,
            'raw_payload' => null,
        ]);

        $start = Carbon::parse('2026-08-01 11:00:00', 'UTC');
        $end = Carbon::parse('2026-08-01 12:00:00', 'UTC');

        $svc = app(AvailabilityService::class);
        $this->assertTrue($svc->isAvailable($unit, $start, $end));
        $this->assertTrue($svc->getExternalSchedulingSoftWarnings($unit, $start, $end)->isEmpty());
    }
}
