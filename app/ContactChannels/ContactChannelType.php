<?php

namespace App\ContactChannels;

/**
 * Идентификаторы каналов в реестре и в JSON (registry-backed).
 */
enum ContactChannelType: string
{
    case Phone = 'phone';

    case Whatsapp = 'whatsapp';

    case Telegram = 'telegram';

    case Vk = 'vk';

    case Max = 'max';

    /**
     * @return list<self>
     */
    public static function allForTenantConfig(): array
    {
        return [
            self::Phone,
            self::Whatsapp,
            self::Telegram,
            self::Vk,
            self::Max,
        ];
    }

    /**
     * Каналы, которые могут быть выбраны как preferred (включая «только телефон»).
     *
     * @return list<self>
     */
    public static function preferredSelectable(): array
    {
        return [
            self::Phone,
            self::Whatsapp,
            self::Telegram,
            self::Vk,
            self::Max,
        ];
    }
}
