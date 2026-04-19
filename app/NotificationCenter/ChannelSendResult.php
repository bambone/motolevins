<?php

namespace App\NotificationCenter;

use App\Jobs\DispatchNotificationDeliveryJob;
use App\Models\NotificationDelivery;
use Illuminate\Support\Carbon;

/**
 * Normalized outcome of a channel send. Drivers return this; {@see DispatchNotificationDeliveryJob}
 * applies it to {@see NotificationDelivery} (status, timestamps, provider id, response_json).
 */
final readonly class ChannelSendResult
{
    /**
     * @param  array<string, mixed>  $responseJson
     */
    public function __construct(
        public NotificationDeliveryStatus $status,
        public ?Carbon $sentAt = null,
        public ?Carbon $deliveredAt = null,
        public ?string $providerMessageId = null,
        public array $responseJson = [],
    ) {}
}
