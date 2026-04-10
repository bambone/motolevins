<?php

declare(strict_types=1);

namespace App\Scheduling\Enums;

enum CalendarProviderType: string
{
    case Google = 'google';

    case Yandex = 'yandex';

    case Mailru = 'mailru';

    public function label(): string
    {
        return match ($this) {
            self::Google => 'Google Calendar',
            self::Yandex => 'Яндекс Календарь (CalDAV / OAuth)',
            self::Mailru => 'Mail.ru Календарь (CalDAV)',
        };
    }
}
