<?php

return [

    'brand_name' => 'RentBase',

    'organization' => [
        'name' => 'RentBase',
        'url' => env('PLATFORM_MARKETING_ORG_URL', 'https://rentbase.su'),
        'description' => 'Платформа для бизнеса с онлайн-записью, бронированиями и управлением клиентами.',
    ],

    /*
    | Hero: c — основной (план), b/a — запасные (конфиг или A/B).
    */
    'hero_variant' => env('PLATFORM_MARKETING_HERO', 'c'),

    'hero' => [
        'a' => 'Запустите сервисный бизнес с онлайн-записью за 1 день',
        'b' => 'Платформа для управления услугами, записями и клиентами',
        'c' => 'Операционная система для бизнеса с бронированиями',
    ],

    'hero_subtitle' => 'Сайт, расписание, заявки, клиенты и управление — в одной системе.',

    'cta' => [
        'primary' => 'Запустить проект',
        'secondary' => 'Посмотреть демо',
        'discuss' => 'Обсудить проект',
        'final_headline' => 'Запустить платформу для своего бизнеса',
    ],

    /*
    | Демо: внешняя ссылка или путь. По умолчанию — контакты.
    */
    'demo_url' => env('PLATFORM_MARKETING_DEMO_URL', ''),

    'trust' => [
        'businesses' => env('PLATFORM_MARKETING_TRUST_BUSINESSES', '30+'),
        'applications' => env('PLATFORM_MARKETING_TRUST_APPLICATIONS', '1200+'),
    ],

    'kpi' => [
        [
            'value' => env('PLATFORM_MARKETING_KPI_LEADS', '1200+'),
            'label' => 'Заявок обработано',
            'why' => 'Спрос и рабочая воронка — платформа в бою, а не «на бумаге».',
        ],
        [
            'value' => env('PLATFORM_MARKETING_KPI_PROJECTS', '30+'),
            'label' => 'Проектов на платформе',
            'why' => 'Живой продукт и реальная практика, а не эксперимент.',
        ],
        [
            'value' => env('PLATFORM_MARKETING_KPI_GROWTH', 'Рост'),
            'label' => 'Динамика',
            'why' => 'Инфраструктура и продукт развиваются вместе с клиентами.',
        ],
    ],

    'entity_core' => 'RentBase — платформа для бизнеса с бронированиями и управлением: сайт на домене клиента, онлайн-запись и слоты, заявки, статусы и клиенты в одной системе. Подходит для проката, курсов, инструкторов и сервисов по записи.',

    'cases' => [
        [
            'title' => 'Moto Levins',
            'type' => 'Прокат мотоциклов',
            'url' => env('PLATFORM_MARKETING_CASE_MOTOLEVINS', 'https://motolevins.rentbase.su'),
            'real' => true,
        ],
        [
            'title' => 'Скоро',
            'type' => 'Ваш проект может быть здесь',
            'url' => null,
            'real' => false,
        ],
        [
            'title' => 'Скоро',
            'type' => 'Кейс в подборе',
            'url' => null,
            'real' => false,
        ],
    ],

    'pricing' => [
        'basic' => [
            'name' => 'Базовый',
            'launch' => 5000,
            'monthly' => 2000,
            'bullets' => [
                'Шаблонный дизайн',
                'Полный функционал платформы',
            ],
        ],
        'custom' => [
            'name' => 'Кастомный',
            'launch' => 20000,
            'monthly' => 2000,
            'bullets' => [
                'Уникальный дизайн',
                'Полный функционал',
            ],
        ],
        'individual' => [
            'name' => 'Индивидуальный',
            'launch' => null,
            'monthly' => null,
            'bullets' => [
                'Под задачи и процессы клиента',
                'Сроки и объём — по согласованию',
            ],
        ],
    ],

    /*
    | Бэклог маршрутов и статей (content-clusters) — для llms.txt и дорожной карты контента.
    */
    'content_backlog_paths' => [
        '/for-driving-instructors',
        '/for-courses-and-masterclasses',
        '/for-booking-based-services',
    ],

    'llms_summary' => <<<'TXT'
RentBase — платформа (SaaS) для сервисного бизнеса: сайт клиента, онлайн-запись и бронирования, заявки, клиенты и админка. Отдельные страницы: возможности, тарифы, FAQ, контакты, вертикали проката мото/авто.
TXT,

];
