<?php

namespace App\Bookings\Calendar;

use Carbon\CarbonInterface;

/**
 * Normalized interval for overlap math and FullCalendar payload.
 * For all-day path, {@see $end} is exclusive instant (start of day after last included calendar day).
 */
final class BookingCalendarNormalizedInterval
{
    public function __construct(
        public CarbonInterface $start,
        public CarbonInterface $end,
        public bool $allDay,
        public bool $valid,
    ) {}

    public function overlaps(self $other): bool
    {
        if (! $this->valid || ! $other->valid) {
            return false;
        }

        return $this->start->lt($other->end) && $this->end->gt($other->start);
    }
}
