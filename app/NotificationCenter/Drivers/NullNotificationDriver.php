<?php

namespace App\NotificationCenter\Drivers;

use App\Models\NotificationDelivery;
use App\Models\NotificationDestination;
use App\Models\NotificationEvent;
use App\NotificationCenter\Contracts\NotificationChannelDriver;
use App\NotificationCenter\UnsupportedNotificationChannelException;

/**
 * Placeholder until channel is implemented; does not trigger queue retries.
 */
final class NullNotificationDriver implements NotificationChannelDriver
{
    public function __construct(
        private readonly string $reason = 'Channel driver not implemented',
    ) {}

    public function send(
        NotificationDelivery $delivery,
        NotificationEvent $event,
        NotificationDestination $destination,
    ): void {
        throw new UnsupportedNotificationChannelException($this->reason);
    }
}
