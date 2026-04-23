<?php

namespace App\Services\Telegram;

use App\Models\PlatformSetting;

/**
 * Resolves Telegram bot reply texts, flags, and safe defaults from platform_settings.
 */
final class TelegramBotContentResolver
{
    public const KEY_REPLY_START = 'notification.telegram_bot.reply_start';

    public const KEY_REPLY_HELP = 'notification.telegram_bot.reply_help';

    public const KEY_REPLY_STATUS = 'notification.telegram_bot.reply_status';

    public const KEY_REPLY_UNKNOWN = 'notification.telegram_bot.reply_unknown_command';

    public const KEY_PRIVATE_ONLY = 'notification.telegram_bot.private_only';

    public const KEY_WEBHOOK_SECRET_ENABLED = 'notification.telegram_bot.webhook_secret_enabled';

    public function replyStart(): string
    {
        return $this->stringOrDefault(
            self::KEY_REPLY_START,
            "Добро пожаловать в RentBase.\n\n"
            ."Здесь вы можете получать уведомления о заявках и важных событиях платформы.\n\n"
            .'Команды: /help — справка, /status — статус.'
        );
    }

    public function replyHelp(): string
    {
        return $this->stringOrDefault(
            self::KEY_REPLY_HELP,
            "RentBase Bot: уведомления с платформы RentBase.\n\n"
            ."/start — приветствие\n"
            ."/status — краткий статус\n"
            .'/help — эта справка'
        );
    }

    public function replyStatus(): string
    {
        return $this->stringOrDefault(
            self::KEY_REPLY_STATUS,
            'Бот активен. Уведомления доступны, если включён канал Telegram и задан токен в настройках платформы.'
        );
    }

    public function replyUnknownCommand(): string
    {
        return $this->stringOrDefault(
            self::KEY_REPLY_UNKNOWN,
            'Команда не распознана. Введите /help для списка команд.'
        );
    }

    public function isPrivateOnly(): bool
    {
        $v = PlatformSetting::get(self::KEY_PRIVATE_ONLY, null);
        if ($v === null) {
            return true;
        }

        return (bool) $v;
    }

    /**
     * When true, incoming webhook requests must present a matching X-Telegram-Bot-Api-Secret-Token if TELEGRAM_WEBHOOK_SECRET is set.
     */
    public function isWebhookSecretCheckEnabled(): bool
    {
        $v = PlatformSetting::get(self::KEY_WEBHOOK_SECRET_ENABLED, null);
        if ($v === null) {
            return false;
        }

        return (bool) $v;
    }

    private function stringOrDefault(string $key, string $default): string
    {
        $raw = PlatformSetting::get($key, '');
        if (! is_string($raw)) {
            return $default;
        }
        $trim = trim($raw);

        return $trim !== '' ? $trim : $default;
    }
}
