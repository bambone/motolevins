<?php

declare(strict_types=1);

namespace App\Scheduling\Occupancy;

use App\Models\ExternalBusyBlock;
use App\Models\SchedulingResource;
use Carbon\Carbon;

/**
 * Reads cached external busy blocks (populated by calendar sync jobs).
 */
final class ExternalCalendarOccupancyProvider
{
    /**
     * @return list<array{start: Carbon, end: Carbon, is_tentative: bool}>
     */
    public function intervalsFor(
        SchedulingResource $resource,
        Carbon $rangeStartUtc,
        Carbon $rangeEndUtc,
    ): array {
        $rows = ExternalBusyBlock::query()
            ->where('scheduling_resource_id', $resource->id)
            ->where('ends_at_utc', '>', $rangeStartUtc)
            ->where('starts_at_utc', '<', $rangeEndUtc)
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'start' => $r->starts_at_utc,
                'end' => $r->ends_at_utc,
                'is_tentative' => (bool) $r->is_tentative,
            ];
        }

        return $out;
    }
}
