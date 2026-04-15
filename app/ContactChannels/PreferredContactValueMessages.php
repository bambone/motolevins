<?php

namespace App\ContactChannels;

/**
 * Единые тексты валидации preferred_contact_value для публичных форм (RU).
 */
final class PreferredContactValueMessages
{
    public static function requiredRu(string $channelId): string
    {
        return match ($channelId) {
            ContactChannelType::Vk->value => 'Укажите контакт VK, чтобы мы могли связаться с вами этим способом.',
            ContactChannelType::Telegram->value => 'Укажите контакт Telegram, чтобы мы могли связаться с вами этим способом.',
            ContactChannelType::Max->value => 'Укажите контакт MAX, чтобы мы могли связаться с вами этим способом.',
            default => 'Укажите контакт для выбранного способа связи.',
        };
    }

    public static function invalidFormatRu(string $channelId): string
    {
        return match ($channelId) {
            ContactChannelType::Vk->value => 'Укажите ссылку на профиль VK или короткое имя (ник), например vk.com/username.',
            ContactChannelType::Telegram->value => 'Укажите корректный Telegram (username или ссылка https://t.me/…).',
            ContactChannelType::Max->value => 'Укажите контакт MAX (текст или ссылку).',
            default => 'Проверьте контакт для выбранного способа связи.',
        };
    }
}
