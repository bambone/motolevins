<?php

namespace App\NotificationCenter\Presenters;

use App\Models\Booking;
use App\Models\Tenant;
use App\NotificationCenter\NotificationActionUrlBuilder;
use App\NotificationCenter\NotificationPayloadDto;

final class BookingNotificationPresenter
{
    public function __construct(
        private readonly NotificationActionUrlBuilder $urls,
    ) {}

    public function payloadForCreated(Tenant $tenant, Booking $booking): NotificationPayloadDto
    {
        $actionUrl = $this->urls->urlForSubject($tenant, class_basename(Booking::class), (int) $booking->id);
        $body = ($booking->customer_name ?? 'Клиент')."\n".$booking->phone;
        if ($booking->start_date && $booking->end_date) {
            $body .= "\n".$booking->start_date->format('d.m.Y').' — '.$booking->end_date->format('d.m.Y');
        }

        return new NotificationPayloadDto(
            title: 'Новое бронирование',
            body: $body,
            actionUrl: $actionUrl,
            actionLabel: 'Открыть бронирование',
            meta: ['booking_number' => $booking->booking_number],
        );
    }

    public function payloadForCancelled(Tenant $tenant, Booking $booking): NotificationPayloadDto
    {
        $actionUrl = $this->urls->urlForSubject($tenant, class_basename(Booking::class), (int) $booking->id);

        return new NotificationPayloadDto(
            title: 'Бронирование отменено',
            body: ($booking->booking_number ?? '#'.$booking->id).' — '.$booking->customer_name,
            actionUrl: $actionUrl,
            actionLabel: 'Открыть бронирование',
            meta: [],
        );
    }
}
