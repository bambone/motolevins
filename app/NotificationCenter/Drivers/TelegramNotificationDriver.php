<?php

namespace App\NotificationCenter\Drivers;

use App\Models\NotificationDelivery;
use App\Models\NotificationDestination;
use App\Models\NotificationEvent;
use App\NotificationCenter\Contracts\NotificationChannelDriver;
use App\NotificationCenter\NotificationDeliveryStatus;
use App\Services\Platform\PlatformNotificationSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

final class TelegramNotificationDriver implements NotificationChannelDriver
{
    public function __construct(
        private readonly PlatformNotificationSettings $platform,
    ) {}

    public function send(
        NotificationDelivery $delivery,
        NotificationEvent $event,
        NotificationDestination $destination,
    ): void {
        $token = $this->platform->telegramBotTokenDecrypted();
        if ($token === null) {
            throw new \RuntimeException('Telegram bot token is not configured on platform.');
        }

        $chatId = $destination->config_json['chat_id'] ?? null;
        if ($chatId === null || $chatId === '') {
            throw new \InvalidArgumentException('Telegram destination requires chat_id in config.');
        }

        $payload = $event->payloadDto();
        $text = $payload->title."\n\n".$payload->body;
        if ($payload->actionUrl) {
            $text .= "\n\n".$payload->actionUrl;
        }

        $response = Http::timeout(15)
            ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'disable_web_page_preview' => true,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Telegram API error: '.$response->body());
        }

        $messageId = $response->json('result.message_id');

        $delivery->update([
            'status' => NotificationDeliveryStatus::Sent->value,
            'sent_at' => Carbon::now(),
            'delivered_at' => Carbon::now(),
            'provider_message_id' => $messageId !== null ? (string) $messageId : null,
            'response_json' => $response->json(),
        ]);
    }
}
