<?php

declare(strict_types=1);

namespace App\Scheduling;

use App\Models\CalendarOccupancyMapping;
use App\Models\CalendarSubscription;
use App\Models\ExternalBusyBlock;
use App\Models\SchedulingTarget;
use Carbon\Carbon;

/**
 * Единая проверка «протухшего» кэша busy по подпискам, связанным с target.
 */
final class SchedulingStaleBusyEvaluator
{
    public function subscriptionsAreStaleForTarget(SchedulingTarget $target): bool
    {
        $resourceIds = $target->schedulingResources()->pluck('scheduling_resources.id');
        if ($resourceIds->isEmpty()) {
            return false;
        }

        $fromBlocks = ExternalBusyBlock::query()
            ->whereIn('scheduling_resource_id', $resourceIds)
            ->whereNotNull('calendar_subscription_id')
            ->distinct()
            ->pluck('calendar_subscription_id');

        $fromMappings = CalendarOccupancyMapping::query()
            ->where('scheduling_target_id', $target->id)
            ->where('is_active', true)
            ->pluck('calendar_subscription_id');

        $ids = $fromBlocks->merge($fromMappings)->unique()->filter()->values();
        if ($ids->isEmpty()) {
            return false;
        }

        $now = Carbon::now('UTC');
        foreach (CalendarSubscription::query()->whereIn('id', $ids)->where('use_for_busy', true)->where('is_active', true)->cursor() as $sub) {
            $threshold = $sub->stale_after_seconds;
            if ($threshold === null || $threshold <= 0) {
                continue;
            }
            if ($sub->last_successful_sync_at === null) {
                return true;
            }
            if ($now->gt($sub->last_successful_sync_at->copy()->addSeconds($threshold))) {
                return true;
            }
        }

        return false;
    }
}
