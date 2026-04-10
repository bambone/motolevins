<?php

declare(strict_types=1);

namespace App\Scheduling\Occupancy;

use App\Models\AppointmentHold;
use App\Models\ManualBusyBlock;
use App\Models\SchedulingResource;
use App\Models\SchedulingTarget;
use Carbon\Carbon;

/**
 * Aggregates manual busy blocks and appointment holds for a resource (internal sources).
 */
final class InternalSchedulingOccupancyProvider
{
    /**
     * @return list<array{start: Carbon, end: Carbon}>
     */
    public function intervalsFor(
        SchedulingResource $resource,
        SchedulingTarget $target,
        Carbon $rangeStartUtc,
        Carbon $rangeEndUtc,
    ): array {
        $out = [];

        $manual = ManualBusyBlock::query()
            ->where('scheduling_resource_id', $resource->id)
            ->where(function ($q) use ($target) {
                $q->whereNull('scheduling_target_id')->orWhere('scheduling_target_id', $target->id);
            })
            ->where('ends_at_utc', '>', $rangeStartUtc)
            ->where('starts_at_utc', '<', $rangeEndUtc)
            ->get();

        foreach ($manual as $m) {
            $out[] = ['start' => $m->starts_at_utc, 'end' => $m->ends_at_utc];
        }

        $holds = AppointmentHold::query()
            ->where('scheduling_resource_id', $resource->id)
            ->where('ends_at_utc', '>', $rangeStartUtc)
            ->where('starts_at_utc', '<', $rangeEndUtc)
            ->get();

        foreach ($holds as $h) {
            $out[] = ['start' => $h->starts_at_utc, 'end' => $h->ends_at_utc];
        }

        return $out;
    }
}
