<?php

namespace App\Services;

use App\Models\AvailabilityCalendar;
use App\Models\Booking;
use App\Models\RentalUnit;
use App\Models\SchedulingTarget;
use App\Scheduling\Enums\ExternalBusyEffect;
use App\Scheduling\Enums\SchedulingScope;
use App\Scheduling\Enums\SchedulingTargetType;
use App\Scheduling\Enums\TentativeEventsPolicy;
use App\Scheduling\Occupancy\ExternalCalendarOccupancyProvider;
use App\Scheduling\Occupancy\RentalAvailabilityBridge;
use App\Scheduling\SchedulingStaleBusyEvaluator;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class AvailabilityService
{
    public function __construct(
        private readonly RentalAvailabilityBridge $rentalAvailabilityBridge,
        private readonly ExternalCalendarOccupancyProvider $externalCalendarOccupancy,
        private readonly SchedulingStaleBusyEvaluator $staleBusyEvaluator,
    ) {}

    /**
     * Check if a rental unit is available for the given date range.
     */
    public function isAvailable(RentalUnit $rentalUnit, Carbon $start, Carbon $end, ?int $excludeBookingId = null): bool
    {
        $conflicts = $this->getConflicts($rentalUnit, $start, $end, $excludeBookingId);

        return $conflicts->isEmpty();
    }

    /**
     * Get conflicting availability entries for the given range.
     */
    public function getConflicts(RentalUnit $rentalUnit, Carbon $start, Carbon $end, ?int $excludeBookingId = null): Collection
    {
        $calendar = AvailabilityCalendar::query()
            ->where('rental_unit_id', $rentalUnit->id)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('starts_at', [$start, $end])
                    ->orWhereBetween('ends_at', [$start, $end])
                    ->orWhere(function ($q2) use ($start, $end) {
                        $q2->where('starts_at', '<=', $start)
                            ->where('ends_at', '>=', $end);
                    });
            })
            ->whereIn('status', ['blocked', 'booked'])
            ->when($excludeBookingId, fn ($q) => $q->where('booking_id', '!=', $excludeBookingId)->orWhereNull('booking_id'))
            ->get();

        return $calendar->concat($this->externalSchedulingConflictPlaceholders($rentalUnit, $start, $end));
    }

    /**
     * Block slots for a booking.
     */
    public function blockForBooking(Booking $booking): void
    {
        if (! $booking->rental_unit_id || ! $booking->start_at || ! $booking->end_at) {
            return;
        }

        AvailabilityCalendar::create([
            'rental_unit_id' => $booking->rental_unit_id,
            'starts_at' => $booking->start_at,
            'ends_at' => $booking->end_at,
            'status' => 'booked',
            'source' => 'booking',
            'booking_id' => $booking->id,
        ]);
    }

    /**
     * Remove blocks when a booking is cancelled.
     */
    public function unblockForBooking(Booking $booking): void
    {
        AvailabilityCalendar::query()
            ->where('booking_id', $booking->id)
            ->delete();
    }

    /**
     * Create manual block.
     */
    public function createBlock(RentalUnit $rentalUnit, Carbon $start, Carbon $end, string $reason = '', ?int $userId = null): AvailabilityCalendar
    {
        return AvailabilityCalendar::create([
            'rental_unit_id' => $rentalUnit->id,
            'starts_at' => $start,
            'ends_at' => $end,
            'status' => 'blocked',
            'source' => 'manual',
            'reason' => $reason,
            'created_by' => $userId,
        ]);
    }

    /**
     * Get available date ranges for a rental unit within a period.
     */
    public function getAvailableRanges(RentalUnit $rentalUnit, Carbon $from, Carbon $to, int $minDays = 1): array
    {
        $blocks = AvailabilityCalendar::query()
            ->where('rental_unit_id', $rentalUnit->id)
            ->whereIn('status', ['blocked', 'booked'])
            ->where('ends_at', '>=', $from)
            ->where('starts_at', '<=', $to)
            ->orderBy('starts_at')
            ->get();

        $period = CarbonPeriod::create($from, $to);
        $available = [];
        $currentStart = null;

        foreach ($period as $date) {
            $dayStart = $date->copy()->startOfDay();
            $dayEnd = $date->copy()->endOfDay();
            $isBlocked = $blocks->contains(fn ($b) => $dayStart->lte($b->ends_at) && $dayEnd->gte($b->starts_at));

            if (! $isBlocked) {
                if ($currentStart === null) {
                    $currentStart = $date->copy();
                }
            } else {
                if ($currentStart !== null) {
                    $rangeEnd = $date->copy()->subDay();
                    if ($currentStart->diffInDays($rangeEnd) + 1 >= $minDays) {
                        $available[] = ['start' => $currentStart, 'end' => $rangeEnd];
                    }
                    $currentStart = null;
                }
            }
        }

        if ($currentStart !== null && $currentStart->diffInDays($to) + 1 >= $minDays) {
            $available[] = ['start' => $currentStart, 'end' => $to->copy()];
        }

        return $available;
    }

    /**
     * Synthetic conflict rows for rental UI / validation when external scheduling occupancy blocks the range.
     *
     * @return Collection<int, object{starts_at: Carbon, ends_at: Carbon, status: string, source: string}>
     */
    private function externalSchedulingConflictPlaceholders(RentalUnit $rentalUnit, Carbon $start, Carbon $end): Collection
    {
        $target = $this->schedulingTargetForRentalUnit($rentalUnit);
        if ($target === null || ! $target->external_busy_enabled) {
            return collect();
        }

        if (! $this->rentalAvailabilityBridge->shouldHardBlockRental($target, $rentalUnit->tenant)) {
            return collect();
        }

        $startUtc = $start->copy()->utc();
        $endUtc = $end->copy()->utc();

        if ($this->rentalAvailabilityBridge->staleDataBlocksNewSlots($target, $rentalUnit->tenant)
            && $this->staleBusyEvaluator->subscriptionsAreStaleForTarget($target)) {
            return collect([(object) [
                'starts_at' => $startUtc,
                'ends_at' => $endUtc,
                'status' => 'blocked',
                'source' => 'scheduling_stale_external',
            ]]);
        }

        $out = [];
        $resources = $target->schedulingResources()->where('scheduling_resources.is_active', true)->get();
        foreach ($resources as $resource) {
            foreach ($this->externalCalendarOccupancy->intervalsFor($resource, $startUtc, $endUtc) as $interval) {
                if (! $this->rentalAvailabilityBridge->overlaps($startUtc, $endUtc, $interval['start'], $interval['end'])) {
                    continue;
                }
                if ($interval['is_tentative'] && $resource->tentative_events_policy === TentativeEventsPolicy::TreatAsFree) {
                    continue;
                }
                $out[] = (object) [
                    'starts_at' => $interval['start']->copy(),
                    'ends_at' => $interval['end']->copy(),
                    'status' => 'blocked',
                    'source' => 'external_calendar',
                ];
            }
        }

        return collect($out);
    }

    /**
     * Мягкое предупреждение для оператора: пересечение с внешним busy при {@see ExternalBusyEffect::SoftWarning}.
     *
     * @return Collection<int, object{starts_at: Carbon, ends_at: Carbon, status: string, source: string}>
     */
    public function getExternalSchedulingSoftWarnings(RentalUnit $rentalUnit, Carbon $start, Carbon $end): Collection
    {
        $target = $this->schedulingTargetForRentalUnit($rentalUnit);
        if ($target === null || ! $target->external_busy_enabled) {
            return collect();
        }

        if ($this->rentalAvailabilityBridge->effectiveExternalBusyEffect($target, $rentalUnit->tenant) !== ExternalBusyEffect::SoftWarning) {
            return collect();
        }

        $startUtc = $start->copy()->utc();
        $endUtc = $end->copy()->utc();

        $out = [];
        $resources = $target->schedulingResources()->where('scheduling_resources.is_active', true)->get();
        foreach ($resources as $resource) {
            foreach ($this->externalCalendarOccupancy->intervalsFor($resource, $startUtc, $endUtc) as $interval) {
                if (! $this->rentalAvailabilityBridge->overlaps($startUtc, $endUtc, $interval['start'], $interval['end'])) {
                    continue;
                }
                if ($interval['is_tentative'] && $resource->tentative_events_policy === TentativeEventsPolicy::TreatAsFree) {
                    continue;
                }
                $out[] = (object) [
                    'starts_at' => $interval['start']->copy(),
                    'ends_at' => $interval['end']->copy(),
                    'status' => 'warning',
                    'source' => 'external_calendar_soft',
                ];
            }
        }

        return collect($out);
    }

    private function schedulingTargetForRentalUnit(RentalUnit $rentalUnit): ?SchedulingTarget
    {
        return SchedulingTarget::query()
            ->where('scheduling_scope', SchedulingScope::Tenant)
            ->where('tenant_id', $rentalUnit->tenant_id)
            ->where('target_type', SchedulingTargetType::RentalUnit)
            ->where('target_id', $rentalUnit->id)
            ->where('is_active', true)
            ->first();
    }
}
