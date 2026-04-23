<?php

namespace App\Services\Telegram;

use App\Services\Notifications\TelegramTextSender;
use App\Services\Platform\PlatformNotificationSettings;
use Illuminate\Support\Facades\Log;
use Throwable;

final class TelegramWebhookHandler
{
    public function __construct(
        private readonly PlatformNotificationSettings $notificationSettings,
        private readonly TelegramBotContentResolver $contentResolver,
        private readonly TelegramCommandParser $commandParser,
        private readonly TelegramTextSender $telegramText,
    ) {}

    /**
     * @param  array<string, mixed>  $update
     */
    public function handle(array $update): void
    {
        $updateType = $this->detectUpdateType($update);
        Log::info('telegram_webhook: received', [
            'update_type' => $updateType,
        ]);

        if ($updateType !== 'message') {
            return;
        }

        $message = $update['message'] ?? null;
        if (! is_array($message)) {
            return;
        }

        $chat = $message['chat'] ?? null;
        if (! is_array($chat)) {
            return;
        }

        $chatType = (string) ($chat['type'] ?? '');
        $chatId = isset($chat['id']) ? (string) $chat['id'] : null;
        if ($chatId === null || $chatId === '') {
            Log::notice('telegram_webhook: missing_chat_id');

            return;
        }

        if ($this->contentResolver->isPrivateOnly() && $chatType !== 'private') {
            Log::info('telegram_webhook: skipped_non_private_chat', [
                'chat_type' => $chatType,
                'chat_id' => $chatId,
            ]);

            return;
        }

        $text = $message['text'] ?? null;
        if (! is_string($text)) {
            return;
        }

        $parsed = $this->commandParser->parse($text);
        if ($parsed === null) {
            Log::debug('telegram_webhook: not_a_command', ['chat_id' => $chatId]);

            return;
        }

        $command = $parsed['command'];
        $payload = $parsed['payload'];
        Log::info('telegram_webhook: command', [
            'command' => $command,
            'has_payload' => $payload !== null,
            'chat_id' => $chatId,
        ]);

        $body = match ($command) {
            'start' => $this->contentResolver->replyStart(),
            'help' => $this->contentResolver->replyHelp(),
            'status' => $this->contentResolver->replyStatus(),
            default => $this->contentResolver->replyUnknownCommand(),
        };

        $this->sendReplyOrLog($chatId, $body);
    }

    /**
     * @param  array<string, mixed>  $update
     */
    private function detectUpdateType(array $update): string
    {
        foreach (['message', 'edited_message', 'channel_post', 'callback_query', 'my_chat_member'] as $key) {
            if (isset($update[$key])) {
                return $key;
            }
        }

        return 'unknown';
    }

    private function sendReplyOrLog(string $chatId, string $text): void
    {
        if (! $this->notificationSettings->isChannelEnabled('telegram')) {
            Log::notice('telegram_webhook: skipped_channel_disabled');

            return;
        }

        $token = $this->notificationSettings->telegramBotTokenDecrypted();
        if ($token === null || $token === '') {
            Log::notice('telegram_webhook: skipped_no_bot_token');

            return;
        }

        try {
            $this->telegramText->sendPlainText($token, $chatId, $text, null);
            Log::info('telegram_webhook: reply_sent', ['chat_id' => $chatId]);
        } catch (Throwable $e) {
            Log::warning('telegram_webhook: delivery_failed', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
