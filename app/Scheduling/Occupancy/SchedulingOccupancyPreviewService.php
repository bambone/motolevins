<?php

declare(strict_types=1);

namespace App\Scheduling\Occupancy;

use App\Models\SchedulingResource;
use App\Models\SchedulingTarget;
use App\Models\Tenant;
use Carbon\Carbon;

/**
 * Сводка занятости для превью в кабинете (без публичного slot picker).
 */
final class SchedulingOccupancyPreviewService
{
    public function __construct(
        private readonly InternalSchedulingOccupancyProvider $internal,
        private readonly ExternalCalendarOccupancyProvider $external,
    ) {}

    /**
     * @return array{internal: list<array{start: string, end: string}>, external: list<array{start: string, end: string, is_tentative: bool}>}
     */
    public function previewForResource(
        Tenant $tenant,
        SchedulingResource $resource,
        Carbon $rangeStartUtc,
        Carbon $rangeEndUtc,
        ?SchedulingTarget $narrowToTarget = null,
    ): array {
        abort_if((int) $resource->tenant_id !== (int) $tenant->id, 403);

        $internalOut = [];
        if ($narrowToTarget !== null) {
            abort_if((int) $narrowToTarget->tenant_id !== (int) $tenant->id, 403);
            foreach ($this->internal->intervalsFor($resource, $narrowToTarget, $rangeStartUtc, $rangeEndUtc) as $row) {
                $internalOut[] = [
                    'start' => $row['start']->toIso8601String(),
                    'end' => $row['end']->toIso8601String(),
                ];
            }
        }

        $externalOut = [];
        foreach ($this->external->intervalsFor($resource, $rangeStartUtc, $rangeEndUtc) as $row) {
            $externalOut[] = [
                'start' => $row['start']->toIso8601String(),
                'end' => $row['end']->toIso8601String(),
                'is_tentative' => $row['is_tentative'],
            ];
        }

        return ['internal' => $internalOut, 'external' => $externalOut];
    }
}
