<?php

namespace App\Bookings\Calendar;

use App\Models\Booking;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Single source of truth for booking → calendar interval rules (see docs/operations/booking-calendar.md).
 */
final class BookingCalendarRangeNormalizer
{
    /**
     * Timed event only when both start_at and end_at are set; otherwise all-day/day-span from dates.
     */
    public function bookingInterval(Booking $booking, string $timezone): ?BookingCalendarNormalizedInterval
    {
        if ($booking->start_at !== null && $booking->end_at !== null) {
            $hasDateSpan = $booking->start_date !== null && $booking->end_date !== null;
            if (! $hasDateSpan || $this->timedRangeMatchesDateColumns($booking, $timezone)) {
                $start = Carbon::parse($booking->start_at)->timezone($timezone);
                $end = Carbon::parse($booking->end_at)->timezone($timezone);

                return new BookingCalendarNormalizedInterval($start, $end, false, true);
            }
            // Есть и даты, и время, но суточный период по timestamp ≠ колонкам дат — как в CRM показываем по датам.
        }

        if ($booking->start_date === null || $booking->end_date === null) {
            return null;
        }

        $start = Carbon::parse($booking->start_date)->timezone($timezone)->startOfDay();
        $endExclusive = Carbon::parse($booking->end_date)->timezone($timezone)->addDay()->startOfDay();

        return new BookingCalendarNormalizedInterval($start, $endExclusive, true, true);
    }

    /**
     * true, если первый/последний календарный день интервала [start_at, end_at] в TZ тенанта
     * совпадают с start_date и end_date (end_date — последний занятый день, включительно).
     */
    private function timedRangeMatchesDateColumns(Booking $booking, string $timezone): bool
    {
        if ($booking->start_date === null || $booking->end_date === null) {
            return true;
        }

        $sd = $booking->start_date->toDateString();
        $ed = $booking->end_date->toDateString();
        $as = Carbon::parse($booking->start_at)->timezone($timezone);
        $ae = Carbon::parse($booking->end_at)->timezone($timezone);

        if ($as->toDateString() !== $sd) {
            return false;
        }

        $lastOccupied = $ae->copy();
        if ($lastOccupied->isStartOfDay() && $lastOccupied->greaterThan($as->copy()->startOfDay())) {
            $lastOccupied->subDay();
        }

        return $lastOccupied->toDateString() === $ed;
    }

    /**
     * FullCalendar payload fragment: start/end strings and allDay flag.
     *
     * @return array{start: string, end: string, allDay: bool}|null
     */
    public function toFullCalendarTiming(Booking $booking, string $timezone): ?array
    {
        $interval = $this->bookingInterval($booking, $timezone);
        if ($interval === null || ! $interval->valid) {
            return null;
        }

        if ($interval->allDay) {
            return [
                'start' => $interval->start->toDateString(),
                'end' => $interval->end->toDateString(),
                'allDay' => true,
            ];
        }

        return [
            'start' => $interval->start->toIso8601String(),
            'end' => $interval->end->toIso8601String(),
            'allDay' => false,
        ];
    }

    public function tenantTimezone(?string $tenantTimezone): string
    {
        $tz = $tenantTimezone !== null && $tenantTimezone !== ''
            ? $tenantTimezone
            : (string) config('app.timezone', 'UTC');

        return $tz;
    }

    /**
     * Anchor date from query param: valid Y-m-d or today in tenant TZ.
     */
    public function normalizeAnchorDate(?string $dateYmd, string $timezone): string
    {
        if ($dateYmd === null || $dateYmd === '') {
            return Carbon::now($timezone)->toDateString();
        }

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateYmd)) {
            return Carbon::now($timezone)->toDateString();
        }

        try {
            $c = Carbon::createFromFormat('Y-m-d', $dateYmd, $timezone)->startOfDay();

            return $c->toDateString();
        } catch (\Throwable) {
            return Carbon::now($timezone)->toDateString();
        }
    }

    /**
     * @return array{0: CarbonInterface, 1: CarbonInterface} Start inclusive, end exclusive for range limit checks.
     */
    public function parseFetchRange(string $startIso, string $endIso, string $timezone): array
    {
        $start = Carbon::parse($startIso)->timezone($timezone);
        $end = Carbon::parse($endIso)->timezone($timezone);

        return [$start, $end];
    }
}
