<?php

namespace App\NotificationCenter;

use Illuminate\Support\Carbon;

/**
 * Normalized outcome of a channel send. Drivers return this; {@see \App\Jobs\DispatchNotificationDeliveryJob}
 * applies it to {@see \App\Models\NotificationDelivery} (status, timestamps, provider id, response_json).
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
