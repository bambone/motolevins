<?php

declare(strict_types=1);

namespace App\Scheduling\Occupancy;

use App\Models\SchedulingTarget;
use App\Models\Tenant;
use App\Scheduling\Enums\ExternalBusyEffect;
use App\Scheduling\Enums\StaleBusyPolicy;
use Carbon\Carbon;

/**
 * Bridge for rental domain: interprets external scheduling occupancy without mixing rental DB tables.
 */
final class RentalAvailabilityBridge
{
    public function effectiveExternalBusyEffect(SchedulingTarget $target, ?Tenant $tenant): ExternalBusyEffect
    {
        return $target->external_busy_effect;
    }

    /**
     * Whether stale external busy data should block automated rental confirmation (target/tenant policy).
     */
    public function staleDataBlocksNewSlots(SchedulingTarget $target, ?Tenant $tenant): bool
    {
        $policy = $target->stale_busy_policy ?? $tenant?->scheduling_stale_busy_policy;

        return $policy instanceof StaleBusyPolicy && $policy === StaleBusyPolicy::BlockNewSlots;
    }

    /**
     * Hard block: external busy must prevent confirming overlapping rental.
     */
    public function shouldHardBlockRental(SchedulingTarget $target, ?Tenant $tenant): bool
    {
        return $this->effectiveExternalBusyEffect($target, $tenant) === ExternalBusyEffect::HardBlock;
    }

    public function shouldSoftWarnRental(SchedulingTarget $target, ?Tenant $tenant): bool
    {
        return $this->effectiveExternalBusyEffect($target, $tenant) === ExternalBusyEffect::SoftWarning;
    }

    public function staleDataWarnsOnly(SchedulingTarget $target, ?Tenant $tenant): bool
    {
        $policy = $target->stale_busy_policy ?? $tenant?->scheduling_stale_busy_policy;

        return $policy instanceof StaleBusyPolicy && $policy === StaleBusyPolicy::WarnOnly;
    }

    public function overlaps(Carbon $rentalStart, Carbon $rentalEnd, Carbon $busyStart, Carbon $busyEnd): bool
    {
        return $busyStart->lt($rentalEnd) && $busyEnd->gt($rentalStart);
    }
}
