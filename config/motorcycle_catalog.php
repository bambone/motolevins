<?php

/**
 * Словарь чипов каталога: канонический ключ → подпись на сайте.
 * Значения в БД и в fallback лучше хранить как ключи; свободный текст нормализуется через aliases.
 */
return [
    'canonical_labels' => [
        'city' => 'Для города',
        'long_route' => 'Дальний маршрут',
        'passenger' => 'Пассажир',
        'comfortable_seat' => 'Комфортная посадка',
        'automatic' => 'Автомат',
        'variator' => 'Вариатор',
        'beginner' => 'Новичку',
        'highway' => 'Трасса',
        'agile' => 'Манёвренный',
        'easy_ride' => 'Лёгкий в управлении',
        'wind_protection' => 'Ветрозащита',
        'high_seat' => 'Высокая посадка',
        'versatility' => 'Универсальность',
        'travel' => 'Путешествия',
        'mixed_road' => 'Город и трасса',
        'luggage' => 'Кофр / багаж',
        'abs' => 'ABS',
    ],

    /**
     * Нормализованная строка (mb_strtolower, без лишних пробелов) → ключ.
     */
    'aliases' => [
        'для города' => 'city',
        'город' => 'city',
        'дальний маршрут' => 'long_route',
        'дальняк' => 'long_route',
        'пассажир' => 'passenger',
        'для пассажира' => 'passenger',
        'комфортная посадка' => 'comfortable_seat',
        'комфорт' => 'comfortable_seat',
        'автомат' => 'automatic',
        'автоматическая кпп' => 'automatic',
        'dct' => 'automatic',
        'вариатор' => 'variator',
        'новичку' => 'beginner',
        'для новичка' => 'beginner',
        'трасса' => 'highway',
        'манёвренный' => 'agile',
        'маневренный' => 'agile',
        'лёгкий в управлении' => 'easy_ride',
        'легкий в управлении' => 'easy_ride',
        'ветрозащита' => 'wind_protection',
        'высокая посадка' => 'high_seat',
        'универсальность' => 'versatility',
        'путешествия' => 'travel',
        'баланс города и трассы' => 'mixed_road',
        'смешанный сценарий' => 'mixed_road',
        'кофр' => 'luggage',
        'кофр / багаж' => 'luggage',
        'багаж' => 'luggage',
        'abs' => 'abs',
    ],
];
