<?php

namespace App\NotificationCenter\Drivers;

use App\Models\NotificationDelivery;
use App\Models\NotificationDestination;
use App\Models\NotificationEvent;
use App\NotificationCenter\ChannelSendResult;
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
    ): ChannelSendResult {
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

        try {
            Mail::raw($body, function (Message $message) use ($to, $subject): void {
                $message->to($to)->subject($subject);
            });
        } catch (\Throwable $e) {
            throw new \RuntimeException('Email send failed: '.$e->getMessage(), previous: $e);
        }

        $now = Carbon::now();

        return new ChannelSendResult(
            status: NotificationDeliveryStatus::Sent,
            sentAt: $now,
            deliveredAt: null,
            responseJson: ['channel' => 'mail'],
        );
    }
}
