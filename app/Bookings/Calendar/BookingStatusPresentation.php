<?php

namespace App\Bookings\Calendar;

use App\Enums\BookingStatus;

/**
 * Single map for status → UI colors / labels (Filament badges + FullCalendar).
 * JS must not define a parallel status→color map.
 */
final class BookingStatusPresentation
{
    public static function label(BookingStatus $status): string
    {
        return match ($status) {
            BookingStatus::DRAFT => 'Черновик',
            BookingStatus::PENDING => 'Ожидает',
            BookingStatus::AWAITING_PAYMENT => 'Ожидает оплаты',
            BookingStatus::CONFIRMED => 'Подтверждено',
            BookingStatus::CANCELLED => 'Отменено',
            BookingStatus::COMPLETED => 'Завершено',
            BookingStatus::NO_SHOW => 'Неявка',
        };
    }

    /**
     * Filament table badge color name.
     */
    public static function filamentBadgeColor(BookingStatus $status): string
    {
        return match ($status) {
            BookingStatus::CONFIRMED, BookingStatus::COMPLETED => 'success',
            BookingStatus::PENDING, BookingStatus::AWAITING_PAYMENT => 'warning',
            BookingStatus::CANCELLED, BookingStatus::NO_SHOW => 'danger',
            default => 'gray',
        };
    }

    /**
     * FullCalendar + CSS hooks from PHP only.
     *
     * @return array{backgroundColor: string, borderColor: string, classNames: list<string>}
     */
    public static function calendarStyle(BookingStatus $status): array
    {
        return match ($status) {
            BookingStatus::CONFIRMED => [
                'backgroundColor' => 'rgba(34, 197, 94, 0.35)',
                'borderColor' => 'rgb(22, 163, 74)',
                'classNames' => ['booking-cal-status-confirmed'],
            ],
            BookingStatus::PENDING => [
                'backgroundColor' => 'rgba(245, 158, 11, 0.35)',
                'borderColor' => 'rgb(217, 119, 6)',
                'classNames' => ['booking-cal-status-pending'],
            ],
            BookingStatus::AWAITING_PAYMENT => [
                'backgroundColor' => 'rgba(251, 191, 36, 0.35)',
                'borderColor' => 'rgb(245, 158, 11)',
                'classNames' => ['booking-cal-status-awaiting'],
            ],
            BookingStatus::CANCELLED, BookingStatus::NO_SHOW => [
                'backgroundColor' => 'rgba(239, 68, 68, 0.25)',
                'borderColor' => 'rgb(220, 38, 38)',
                'classNames' => ['booking-cal-status-danger'],
            ],
            BookingStatus::COMPLETED => [
                'backgroundColor' => 'rgba(148, 163, 184, 0.35)',
                'borderColor' => 'rgb(100, 116, 139)',
                'classNames' => ['booking-cal-status-completed'],
            ],
            default => [
                'backgroundColor' => 'rgba(113, 113, 122, 0.35)',
                'borderColor' => 'rgb(82, 82, 91)',
                'classNames' => ['booking-cal-status-neutral'],
            ],
        };
    }
}
