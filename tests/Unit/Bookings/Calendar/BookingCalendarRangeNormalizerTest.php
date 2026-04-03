<?php

namespace Tests\Unit\Bookings\Calendar;

use App\Bookings\Calendar\BookingCalendarRangeNormalizer;
use App\Enums\BookingStatus;
use App\Models\Booking;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookingCalendarRangeNormalizerTest extends TestCase
{
    #[Test]
    public function partial_datetime_falls_back_to_all_day_from_dates(): void
    {
        $normalizer = new BookingCalendarRangeNormalizer;
        $booking = new Booking([
            'start_at' => Carbon::parse('2026-04-02 16:30:00', 'UTC'),
            'end_at' => null,
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-03',
            'status' => BookingStatus::PENDING,
        ]);

        $interval = $normalizer->bookingInterval($booking, 'UTC');
        $this->assertNotNull($interval);
        $this->assertTrue($interval->allDay);
        $this->assertTrue($interval->start->isSameDay(Carbon::parse('2026-04-01', 'UTC')));
        $this->assertTrue($interval->end->equalTo(Carbon::parse('2026-04-04', 'UTC')->startOfDay()));
    }

    #[Test]
    public function timed_event_uses_start_at_end_at_when_both_set(): void
    {
        $normalizer = new BookingCalendarRangeNormalizer;
        // Timed branch only when start_date/end_date match calendar span of timestamps (see docs/operations/booking-calendar.md).
        $booking = new Booking([
            'start_at' => Carbon::parse('2026-04-02 16:30:00', 'UTC'),
            'end_at' => Carbon::parse('2026-04-02 17:30:00', 'UTC'),
            'start_date' => '2026-04-02',
            'end_date' => '2026-04-02',
            'status' => BookingStatus::CONFIRMED,
        ]);

        $interval = $normalizer->bookingInterval($booking, 'UTC');
        $this->assertNotNull($interval);
        $this->assertFalse($interval->allDay);
        $this->assertTrue($interval->start->equalTo(Carbon::parse('2026-04-02 16:30:00', 'UTC')));
        $this->assertTrue($interval->end->equalTo(Carbon::parse('2026-04-02 17:30:00', 'UTC')));
    }

    #[Test]
    public function full_calendar_all_day_has_exclusive_end_date_string(): void
    {
        $normalizer = new BookingCalendarRangeNormalizer;
        $booking = new Booking([
            'start_at' => null,
            'end_at' => null,
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-03',
            'status' => BookingStatus::CONFIRMED,
        ]);

        $timing = $normalizer->toFullCalendarTiming($booking, 'UTC');
        $this->assertSame('2026-04-01', $timing['start']);
        $this->assertSame('2026-04-04', $timing['end']);
        $this->assertTrue($timing['allDay']);
    }

    #[Test]
    public function invalid_date_query_falls_back_to_today_placeholder(): void
    {
        $normalizer = new BookingCalendarRangeNormalizer;
        Carbon::setTestNow(Carbon::parse('2026-05-10 12:00:00', 'Europe/Moscow'));

        $ymd = $normalizer->normalizeAnchorDate('not-valid', 'Europe/Moscow');
        $this->assertSame('2026-05-10', $ymd);

        Carbon::setTestNow();
    }
}
