<?php

namespace App\NotificationCenter\Drivers;

use App\Models\NotificationDelivery;
use App\Models\NotificationDestination;
use App\Models\NotificationEvent;
use App\NotificationCenter\Contracts\NotificationChannelDriver;
use App\NotificationCenter\NotificationDeliveryStatus;
use App\NotificationCenter\WebhookUrlValidator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final class WebhookNotificationDriver implements NotificationChannelDriver
{
    public function __construct(
        private readonly WebhookUrlValidator $urlValidator,
    ) {}

    public function send(
        NotificationDelivery $delivery,
        NotificationEvent $event,
        NotificationDestination $destination,
    ): void {
        $url = $destination->config_json['url'] ?? null;
        if (! is_string($url) || $url === '') {
            throw new \InvalidArgumentException('Webhook destination requires url in config.');
        }

        $this->urlValidator->assertSafeHttpsUrl($url);

        $timeout = (int) config('notification_center.webhook.timeout_seconds', 10);
        $maxKb = (int) config('notification_center.webhook.max_payload_kb', 256);

        $body = [
            'event_key' => $event->event_key,
            'tenant_id' => $event->tenant_id,
            'subject_type' => $event->subject_type,
            'subject_id' => $event->subject_id,
            'payload' => $event->payload_json,
            'occurred_at' => $event->occurred_at?->toIso8601String(),
        ];

        $json = json_encode($body, JSON_THROW_ON_ERROR);
        if (strlen($json) > $maxKb * 1024) {
            throw new \RuntimeException('Webhook payload exceeds max size.');
        }

        $headers = [];
        $secret = $destination->config_json['secret'] ?? null;
        if (is_string($secret) && $secret !== '') {
            $sig = hash_hmac('sha256', $json, $secret);
            $headers['X-Notification-Signature'] = $sig;
        }

        $response = Http::timeout($timeout)
            ->withOptions(['allow_redirects' => false])
            ->withHeaders($headers)
            ->withBody($json, 'application/json')
            ->post($url);

        if (! $response->successful()) {
            throw new \RuntimeException('Webhook HTTP '.$response->status().': '.Str::limit($response->body(), 500));
        }

        $delivery->update([
            'status' => NotificationDeliveryStatus::Sent->value,
            'sent_at' => Carbon::now(),
            'delivered_at' => Carbon::now(),
            'response_json' => [
                'status' => $response->status(),
                'body_preview' => Str::limit($response->body(), 2000),
            ],
        ]);
    }
}
