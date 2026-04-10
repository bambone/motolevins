<?php

declare(strict_types=1);

namespace App\Scheduling\Enums;

enum CalendarAccessMode: string
{
    case Oauth = 'oauth';

    case AppPassword = 'app_password';

    case ServiceToken = 'service_token';

    public function label(): string
    {
        return match ($this) {
            self::Oauth => 'OAuth 2.0 (вход через браузер, refresh-токен)',
            self::AppPassword => 'Пароль приложения или пароль CalDAV',
            self::ServiceToken => 'Сервисный аккаунт / JSON-ключ / токен API',
        };
    }

    /**
     * Порядок вариантов в форме: сначала самый типичный сценарий для провайдера.
     *
     * @return list<self>
     */
    public static function orderedForForm(CalendarProviderType $provider): array
    {
        return match ($provider) {
            CalendarProviderType::Google => [self::Oauth, self::AppPassword, self::ServiceToken],
            CalendarProviderType::Yandex => [self::Oauth, self::AppPassword, self::ServiceToken],
            CalendarProviderType::Mailru => [self::AppPassword, self::Oauth, self::ServiceToken],
        };
    }
}
