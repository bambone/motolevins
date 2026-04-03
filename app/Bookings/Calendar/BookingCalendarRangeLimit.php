<?php

namespace App\Bookings\Calendar;

use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

final class BookingCalendarRangeLimit
{
    /**
     * @throws ValidationException
     */
    public static function assertWithinLimit(CarbonInterface $rangeStart, CarbonInterface $rangeEndExclusive): void
    {
        $seconds = abs($rangeEndExclusive->getTimestamp() - $rangeStart->getTimestamp());
        $days = (int) ceil($seconds / 86400);

        if ($days > BookingCalendarConstants::MAX_VISIBLE_RANGE_DAYS) {
            throw ValidationException::withMessages([
                'range' => sprintf(
                    'Слишком большой диапазон календаря (максимум %d дней).',
                    BookingCalendarConstants::MAX_VISIBLE_RANGE_DAYS
                ),
            ]);
        }
    }
}
