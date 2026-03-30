<?php

/**
 * Конфигурация международных телефонов для нормализации/валидации на сервере.
 * Логика должна совпадать с resources/js/tenant-intl-phone.js (при изменениях — синхронизировать).
 *
 * @var list<array{
 *     key: string,
 *     code: string,
 *     national_min: int,
 *     national_max: int,
 *     priority: int,
 *     example: string,
 * }>
 */
return [
    [
        'key' => 'AM',
        'code' => '374',
        'national_min' => 8,
        'national_max' => 8,
        'priority' => 30,
        'example' => '+374 91 234567',
    ],
    [
        'key' => 'AE',
        'code' => '971',
        'national_min' => 9,
        'national_max' => 9,
        'priority' => 30,
        'example' => '+971 50 123 4567',
    ],
    [
        'key' => 'DE',
        'code' => '49',
        'national_min' => 10,
        'national_max' => 11,
        'priority' => 20,
        'example' => '+49 1512 3456789',
    ],
    [
        'key' => 'GB',
        'code' => '44',
        'national_min' => 10,
        'national_max' => 10,
        'priority' => 20,
        'example' => '+44 7700 900123',
    ],
    [
        'key' => 'RU',
        'code' => '7',
        'national_min' => 10,
        'national_max' => 10,
        'priority' => 10,
        'example' => '+7 (999) 123-45-67',
    ],
    [
        'key' => 'NANP',
        'code' => '1',
        'national_min' => 10,
        'national_max' => 10,
        'priority' => 5,
        'example' => '+1 (415) 555-2671',
    ],
];
