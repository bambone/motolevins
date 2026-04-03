<?php

namespace App\Bookings\Calendar;

use App\Models\Booking;
use App\Models\Tenant;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class BookingCalendarEventsService
{
    public function __construct(
        private readonly BookingCalendarRangeNormalizer $normalizer,
        private readonly BookingCalendarQuery $query,
        private readonly BookingCalendarConflictDetector $conflictDetector,
        private readonly BookingCalendarEventPresenter $presenter,
    ) {}

    /**
     * @return list<array<string, mixed>>
     *
     * @throws ValidationException
     */
    public function fetchEvents(
        Tenant $tenant,
        BookingCalendarFiltersData $filters,
        string $rangeStartIso,
        string $rangeEndIso,
    ): array {
        $tz = $this->normalizer->tenantTimezone($tenant->timezone);
        [$rangeStart, $rangeEnd] = $this->normalizer->parseFetchRange($rangeStartIso, $rangeEndIso, $tz);

        BookingCalendarRangeLimit::assertWithinLimit($rangeStart, $rangeEnd);

        $builder = $this->query->baseBuilder($filters, $rangeStart, $rangeEnd);
        /** @var Collection<int, Booking> $bookings */
        $bookings = $builder->orderBy('start_date')->orderBy('id')->get();

        $conflictIds = $this->conflictDetector->conflictingBookingIds($bookings, $this->normalizer, $tz);

        $events = [];
        foreach ($bookings as $booking) {
            $row = $this->presenter->toFullCalendarEvent(
                $booking,
                $this->normalizer,
                $tz,
                $conflictIds,
                $filters->highlightBookingId,
            );
            if ($row !== []) {
                $events[] = $row;
            }
        }

        return $events;
    }
}
