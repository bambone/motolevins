<?php

namespace Tests\Unit\Bookings\Calendar;

use App\Bookings\Calendar\BookingCalendarConflictDetector;
use App\Bookings\Calendar\BookingCalendarRangeNormalizer;
use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookingCalendarConflictDetectorTest extends TestCase
{
    #[Test]
    public function marks_overlap_on_same_rental_unit_only(): void
    {
        $normalizer = new BookingCalendarRangeNormalizer;
        $detector = new BookingCalendarConflictDetector;

        $a = new Booking([
            'rental_unit_id' => 10,
            'motorcycle_id' => 1,
            'start_at' => null,
            'end_at' => null,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-05',
            'status' => BookingStatus::CONFIRMED,
        ]);
        $a->setAttribute('id', 1);
        $b = new Booking([
            'rental_unit_id' => 10,
            'motorcycle_id' => 1,
            'start_at' => null,
            'end_at' => null,
            'start_date' => '2026-06-04',
            'end_date' => '2026-06-10',
            'status' => BookingStatus::PENDING,
        ]);
        $b->setAttribute('id', 2);

        $conflicts = $detector->conflictingBookingIds(new Collection([$a, $b]), $normalizer, 'UTC');
        $this->assertArrayHasKey(1, $conflicts);
        $this->assertArrayHasKey(2, $conflicts);
    }

    #[Test]
    public function does_not_mark_conflict_for_same_motorcycle_without_unit(): void
    {
        $normalizer = new BookingCalendarRangeNormalizer;
        $detector = new BookingCalendarConflictDetector;

        $a = new Booking([
            'rental_unit_id' => null,
            'motorcycle_id' => 5,
            'start_at' => null,
            'end_at' => null,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-05',
            'status' => BookingStatus::CONFIRMED,
        ]);
        $b = new Booking([
            'rental_unit_id' => null,
            'motorcycle_id' => 5,
            'start_at' => null,
            'end_at' => null,
            'start_date' => '2026-06-04',
            'end_date' => '2026-06-10',
            'status' => BookingStatus::PENDING,
        ]);

        $conflicts = $detector->conflictingBookingIds(new Collection([$a, $b]), $normalizer, 'UTC');
        $this->assertSame([], $conflicts);
    }
}
