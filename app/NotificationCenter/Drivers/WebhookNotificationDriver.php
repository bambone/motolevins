<?php

namespace App\NotificationCenter\Drivers;

use App\Models\NotificationDelivery;
use App\Models\NotificationDestination;
use App\Models\NotificationEvent;
use App\NotificationCenter\ChannelSendResult;
use App\NotificationCenter\Contracts\NotificationChannelDriver;
use App\NotificationCenter\NotificationDeliveryStatus;
use App\NotificationCenter\WebhookUrlValidator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * HTTPS POST with JSON body. Headers: `X-Notification-Delivery-Id`, `X-Notification-Event-Id`, `X-Notification-Event-Key`.
 * When `secret` is configured: `X-Notification-Timestamp` (Unix seconds) and `X-Notification-Signature` as
 * `sha256=` + hex HMAC-SHA256 over `"{timestamp}.{raw_json_body}"`.
 */
final class WebhookNotificationDriver implements NotificationChannelDriver
{
    public function __construct(
        private readonly WebhookUrlValidator $urlValidator,
    ) {}

    public function send(
        NotificationDelivery $delivery,
        NotificationEvent $event,
        NotificationDestination $destination,
    ): ChannelSendResult {
        $url = $destination->config_json['url'] ?? null;
        if (! is_string($url) || trim($url) === '') {
            throw new \InvalidArgumentException('Webhook destination requires url in config.');
        }

        $this->urlValidator->assertSafeHttpsUrl($url);

        $timeout = (int) config('notification_center.webhook.timeout_seconds', 10);
        $maxKb = (int) config('notification_center.webhook.max_payload_kb', 256);

        $body = [
            'delivery_id' => $delivery->id,
            'event_id' => $event->id,
            'event_key' => $event->event_key,
            'tenant_id' => $event->tenant_id,
            'subject_type' => $event->subject_type,
            'subject_id' => $event->subject_id,
            'payload' => $event->payload_json,
            'occurred_at' => $event->occurred_at?->toIso8601String(),
        ];

        $json = json_encode(
            $body,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if (strlen($json) > $maxKb * 1024) {
            throw new \RuntimeException('Webhook payload exceeds max size.');
        }

        $headers = [
            'X-Notification-Delivery-Id' => (string) $delivery->id,
            'X-Notification-Event-Id' => (string) $event->id,
            'X-Notification-Event-Key' => (string) $event->event_key,
        ];

        $secret = $destination->config_json['secret'] ?? null;
        if (is_string($secret) && $secret !== '') {
            $timestamp = (string) now()->timestamp;
            $signingPayload = $timestamp.'.'.$json;
            $sig = hash_hmac('sha256', $signingPayload, $secret);
            $headers['X-Notification-Timestamp'] = $timestamp;
            $headers['X-Notification-Signature'] = 'sha256='.$sig;
        }

        try {
            $response = Http::timeout($timeout)
                ->withOptions(['allow_redirects' => false])
                ->withHeaders($headers)
                ->withBody($json, 'application/json')
                ->send('POST', $url);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Webhook request failed: '.$e->getMessage(), previous: $e);
        }

        if (! $response->successful()) {
            throw new \RuntimeException(
                'Webhook HTTP '.$response->status().': '.Str::limit($response->body(), 500)
            );
        }

        $now = Carbon::now();

        return new ChannelSendResult(
            status: NotificationDeliveryStatus::Sent,
            sentAt: $now,
            deliveredAt: null,
            responseJson: [
                'status' => $response->status(),
                'body_preview' => Str::limit($response->body(), 2000),
            ],
        );
    }
}
