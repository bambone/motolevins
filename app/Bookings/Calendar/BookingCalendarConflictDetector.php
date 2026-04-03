<?php

namespace App\Bookings\Calendar;

use App\Models\Booking;

/**
 * Marks conflicts only for same non-null rental_unit_id and valid overlapping intervals (v1).
 */
final class BookingCalendarConflictDetector
{
    /**
     * @param  iterable<Booking>  $bookings
     * @return array<int, true> booking ids that participate in at least one overlap
     */
    public function conflictingBookingIds(iterable $bookings, BookingCalendarRangeNormalizer $normalizer, string $timezone): array
    {
        /** @var array<int, list<array{booking: Booking, interval: BookingCalendarNormalizedInterval}>> $byUnit */
        $byUnit = [];
        foreach ($bookings as $booking) {
            if ($booking->rental_unit_id === null) {
                continue;
            }
            $interval = $normalizer->bookingInterval($booking, $timezone);
            if ($interval === null || ! $interval->valid) {
                continue;
            }
            $uid = (int) $booking->rental_unit_id;
            $byUnit[$uid][] = ['booking' => $booking, 'interval' => $interval];
        }

        $conflicts = [];
        foreach ($byUnit as $group) {
            $m = count($group);
            for ($i = 0; $i < $m; $i++) {
                for ($j = $i + 1; $j < $m; $j++) {
                    if ($group[$i]['interval']->overlaps($group[$j]['interval'])) {
                        $conflicts[$group[$i]['booking']->id] = true;
                        $conflicts[$group[$j]['booking']->id] = true;
                    }
                }
            }
        }

        return $conflicts;
    }
}
