<?php

return [
    /*
    | Локальная отладка: логировать тайминги быстрого сохранения linked-записи с карточки мотоцикла
    | (EditMotorcycle::saveOnlineBookingOnly). На проде держите false.
    */
    'trace_linked_motorcycle_save' => (bool) env('SCHEDULING_TRACE_LINKED_MOTORCYCLE_SAVE', false),

    /*
    | Платформенный дефолт для WriteCalendarResolver (после tenant):
    | PlatformSetting::set('scheduling.default_write_calendar_subscription_id', $id, 'integer');
    */
    'google' => [
        'client_id' => env('SCHEDULING_GOOGLE_CLIENT_ID'),
        'client_secret' => env('SCHEDULING_GOOGLE_CLIENT_SECRET'),
        'redirect_uri' => env('SCHEDULING_GOOGLE_REDIRECT_URI'),
    ],
    'yandex' => [
        'client_id' => env('SCHEDULING_YANDEX_CLIENT_ID'),
        'client_secret' => env('SCHEDULING_YANDEX_CLIENT_SECRET'),
    ],
];
