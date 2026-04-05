<?php

namespace App\NotificationCenter\Drivers;

use App\Models\NotificationDelivery;
use App\Models\NotificationDestination;
use App\Models\NotificationEvent;
use App\NotificationCenter\ChannelSendResult;
use App\NotificationCenter\Contracts\NotificationChannelDriver;
use App\NotificationCenter\NotificationDeliveryStatus;
use App\Services\Platform\PlatformNotificationSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Telegram Bot API sendMessage. Sends plain text only (no parse_mode) so payload is never interpreted as Markdown/HTML.
 */
final class TelegramNotificationDriver implements NotificationChannelDriver
{
    private const int MAX_MESSAGE_LENGTH = 4096;

    public function __construct(
        private readonly PlatformNotificationSettings $platform,
    ) {}

    public function send(
        NotificationDelivery $delivery,
        NotificationEvent $event,
        NotificationDestination $destination,
    ): ChannelSendResult {
        $token = $this->platform->telegramBotTokenDecrypted();
        if ($token === null) {
            throw new \RuntimeException('Telegram bot token is not configured on platform.');
        }

        $chatId = $destination->config_json['chat_id'] ?? null;
        if (! is_string($chatId) || trim($chatId) === '') {
            throw new \InvalidArgumentException('Telegram destination requires chat_id in config.');
        }

        $payload = $event->payloadDto();
        $parts = array_filter([
            trim((string) $payload->title),
            trim((string) $payload->body),
            $payload->actionUrl ? trim((string) $payload->actionUrl) : null,
        ], static fn (?string $value): bool => $value !== null && $value !== '');

        $text = mb_substr(implode("\n\n", $parts), 0, self::MAX_MESSAGE_LENGTH);

        try {
            $response = Http::timeout(15)
                ->asJson()
                ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => trim($chatId),
                    'text' => $text,
                    'disable_web_page_preview' => true,
                ]);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Telegram request failed: '.$e->getMessage(), previous: $e);
        }

        if (! $response->successful()) {
            throw new \RuntimeException('Telegram API error: '.$response->body());
        }

        $messageId = $response->json('result.message_id');
        $now = Carbon::now();
        $decoded = $response->json();

        return new ChannelSendResult(
            status: NotificationDeliveryStatus::Sent,
            sentAt: $now,
            deliveredAt: null,
            providerMessageId: $messageId !== null ? (string) $messageId : null,
            responseJson: is_array($decoded) ? $decoded : ['raw' => $response->body()],
        );
    }
}
