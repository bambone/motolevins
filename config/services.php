<?php

use App\Support\NominatimContactIdentifier;

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    | OpenStreetMap Nominatim (поиск населённых пунктов из backend; см. App\Geocoding).
    | Политика: https://operations.osmfoundation.org/policies/nominatim/
    */
    'nominatim' => [
        'enabled' => env('NOMINATIM_ENABLED', true),
        'base_url' => env('NOMINATIM_BASE_URL', 'https://nominatim.openstreetmap.org'),
        'contact' => env('NOMINATIM_CONTACT')
            ?: NominatimContactIdentifier::resolveForUserAgent(),
        'timeout' => (int) env('NOMINATIM_TIMEOUT', 8),
        'search_cache_ttl' => (int) env('NOMINATIM_SEARCH_CACHE_TTL', 86400),
        'pick_cache_ttl' => (int) env('NOMINATIM_PICK_CACHE_TTL', 86400),
    ],

    /*
    | Telegram: не смешивать с токеном платформенного бота (БД, провайдеры). Здесь — только
    | отдельные сценарии, например уведомления о лидах с публичного сайта (SendLeadTelegramNotification).
    | Webhook для команд бота: TELEGRAM_WEBHOOK_SECRET + путь config('telegram.webhook_path').
    */
    'telegram' => [
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
    ],

    /*
    | VK API (импорт комментариев как кандидатов отзывов). Токен с правами группы при необходимости.
    */
    'vk' => [
        'access_token' => env('VK_SERVICE_ACCESS_TOKEN'),
    ],

];
