<?php

namespace App\Bookings\Calendar;

use App\Filament\Tenant\Resources\BookingResource;
use App\Filament\Tenant\Resources\CrmRequestResource;
use App\Filament\Tenant\Resources\MotorcycleResource;
use App\Filament\Tenant\Resources\RentalUnitResource;
use App\Models\Booking;
use Filament\Facades\Filament;
use Illuminate\Support\Str;

/**
 * Builds FullCalendar-ready event arrays (no extra business normalization in JS).
 */
final class BookingCalendarEventPresenter
{
    public function equipmentLabel(Booking $booking): string
    {
        $unit = $booking->rentalUnit;
        if ($unit !== null) {
            $m = $unit->motorcycle;

            return $m?->name
                ? sprintf('Единица #%d · %s', $unit->id, $m->name)
                : sprintf('Единица #%d', $unit->id);
        }

        $moto = $booking->motorcycle;
        if ($moto !== null) {
            return $moto->name;
        }

        if ($booking->booking_number) {
            return 'Бронь '.$booking->booking_number;
        }

        return 'Бронь #'.$booking->id;
    }

    public function clientLabel(Booking $booking): string
    {
        $name = Str::of(trim((string) $booking->customer_name))->trim();
        if ($name->isNotEmpty()) {
            return (string) $name;
        }

        $lead = $booking->lead;
        if ($lead !== null && filled($lead->name)) {
            return trim((string) $lead->name);
        }

        $customer = $booking->customer;
        if ($customer !== null && filled($customer->full_name)) {
            return trim((string) $customer->full_name);
        }

        $phone = trim((string) $booking->phone);
        if ($phone !== '') {
            return $phone;
        }

        return 'Клиент не указан';
    }

    public function crmUrl(Booking $booking): ?string
    {
        if (Filament::getCurrentPanel()?->getId() !== 'admin') {
            return null;
        }

        $lead = $booking->lead;
        if ($lead === null || $lead->crm_request_id === null) {
            return null;
        }

        $tenant = currentTenant();
        if ($tenant === null || (int) $lead->tenant_id !== (int) $tenant->id) {
            return null;
        }

        try {
            return CrmRequestResource::getUrl('view', ['record' => $lead->crm_request_id]);
        } catch (\Throwable) {
            return null;
        }
    }

    public function bookingUrl(Booking $booking): ?string
    {
        if (Filament::getCurrentPanel()?->getId() !== 'admin') {
            return null;
        }

        try {
            return BookingResource::getUrl('view', ['record' => $booking->id]);
        } catch (\Throwable) {
            return null;
        }
    }

    public function equipmentUrl(Booking $booking): ?string
    {
        if (Filament::getCurrentPanel()?->getId() !== 'admin') {
            return null;
        }

        try {
            if ($booking->rental_unit_id !== null) {
                return RentalUnitResource::getUrl('edit', ['record' => $booking->rental_unit_id]);
            }
            if ($booking->motorcycle_id !== null) {
                return MotorcycleResource::getUrl('edit', ['record' => $booking->motorcycle_id]);
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    /**
     * @param  array<int, true>  $conflictIds
     * @return array<string, mixed>
     */
    public function toFullCalendarEvent(
        Booking $booking,
        BookingCalendarRangeNormalizer $normalizer,
        string $timezone,
        array $conflictIds,
        ?int $highlightBookingId,
    ): array {
        $status = $booking->status;
        $style = BookingStatusPresentation::calendarStyle($status);
        $timing = $normalizer->toFullCalendarTiming($booking, $timezone);
        if ($timing === null) {
            return [];
        }

        $equipment = $this->equipmentLabel($booking);
        $client = $this->clientLabel($booking);
        $title = $equipment.' — '.$client;

        $statusLabel = BookingStatusPresentation::label($status);
        $number = $booking->booking_number ?: ('#'.$booking->id);

        $intervalHuman = $timing['allDay']
            ? ($booking->start_date?->toDateString().' — '.$booking->end_date?->toDateString())
            : ($booking->start_at?->timezone($timezone)->format('d.m.Y H:i').' — '.$booking->end_at?->timezone($timezone)->format('d.m.Y H:i'));

        $subtitleParts = array_filter([
            $statusLabel,
            $timing['allDay'] ? null : ($booking->start_at?->timezone($timezone)->format('H:i').'–'.$booking->end_at?->timezone($timezone)->format('H:i')),
            $number,
        ]);

        $classNames = $style['classNames'];
        $conflict = isset($conflictIds[$booking->id]);
        if ($conflict) {
            $classNames[] = 'booking-cal-conflict';
        }

        $highlighted = $highlightBookingId !== null && (int) $highlightBookingId === (int) $booking->id;
        if ($highlighted) {
            $classNames[] = 'booking-cal-highlighted';
        }

        return [
            'id' => (string) $booking->id,
            'title' => $title,
            'start' => $timing['start'],
            'end' => $timing['end'],
            'allDay' => $timing['allDay'],
            'backgroundColor' => $style['backgroundColor'],
            'borderColor' => $conflict ? '#ef4444' : $style['borderColor'],
            'classNames' => array_values(array_unique($classNames)),
            'extendedProps' => [
                'subtitle' => implode(' · ', $subtitleParts),
                'status' => $status->value,
                'statusLabel' => $statusLabel,
                'bookingNumber' => $number,
                'equipment' => $equipment,
                'client' => $client,
                'phone' => $booking->phone ?: null,
                'intervalHuman' => $intervalHuman,
                'totalPrice' => $booking->total_price,
                'deposit' => $booking->deposit_amount,
                'urls' => [
                    'booking' => $this->bookingUrl($booking),
                    'crm' => $this->crmUrl($booking),
                    'equipment' => $this->equipmentUrl($booking),
                ],
                'conflict' => $conflict,
                'highlighted' => $highlighted,
            ],
        ];
    }
}
