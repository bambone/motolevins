<?php

namespace App\NotificationCenter\Drivers;

use App\Models\NotificationDelivery;
use App\Models\NotificationDestination;
use App\Models\NotificationEvent;
use App\NotificationCenter\Contracts\NotificationChannelDriver;
use App\NotificationCenter\NotificationDeliveryStatus;
use Illuminate\Mail\Message;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

final class EmailNotificationDriver implements NotificationChannelDriver
{
    public function send(
        NotificationDelivery $delivery,
        NotificationEvent $event,
        NotificationDestination $destination,
    ): void {
        $to = $destination->config_json['email'] ?? null;
        if (! is_string($to) || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email destination config.');
        }

        $payload = $event->payloadDto();
        $subject = $payload->title;
        $body = $payload->body;
        if ($payload->actionUrl) {
            $body .= "\n\n".$payload->actionUrl;
        }

        Mail::raw($body, function (Message $message) use ($to, $subject): void {
            $message->to($to)->subject($subject);
        });

        $delivery->update([
            'status' => NotificationDeliveryStatus::Sent->value,
            'sent_at' => Carbon::now(),
            'delivered_at' => Carbon::now(),
        ]);
    }
}
