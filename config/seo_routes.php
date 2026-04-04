<?php

use App\Services\Seo\SeoRouteRegistry;

/**
 * Tenant public SEO templates by Laravel route name.
 * Placeholders: {site_name}, {page_name}, {motorcycle_name}
 *
 * @see SeoRouteRegistry
 */
return [
    'routes' => [
        'home' => [
            'title' => '{site_name}',
            'description' => 'Аренда мототехники и сервис {site_name}. Выберите модель, даты и оформите заявку онлайн.',
            'h1' => '{site_name}',
        ],
        'offline' => [
            'title' => 'Офлайн — {site_name}',
            'description' => 'Страница доступна без сети.',
            'h1' => 'Офлайн',
        ],
        'contacts' => [
            'title' => 'Контакты — {site_name}',
            'description' => 'Свяжитесь с {site_name}: телефон, мессенджеры и способы связи.',
            'h1' => 'Контакты',
        ],
        'terms' => [
            'title' => 'Условия аренды — {site_name}',
            'description' => 'Условия аренды и правила проката у {site_name}.',
            'h1' => 'Условия аренды',
        ],
        'motorcycles.index' => [
            'title' => 'Каталог мотоциклов — {site_name}',
            'description' => 'Каталог доступной мототехники для аренды. Выберите модель и даты.',
            'h1' => 'Каталог',
        ],
        'prices' => [
            'title' => 'Цены — {site_name}',
            'description' => 'Тарифы и стоимость аренды у {site_name}.',
            'h1' => 'Цены',
        ],
        'order' => [
            'title' => 'Заказ — {site_name}',
            'description' => 'Оформление заказа аренды у {site_name}.',
            'h1' => 'Заказ',
        ],
        'reviews' => [
            'title' => 'Отзывы — {site_name}',
            'description' => 'Отзывы клиентов {site_name}.',
            'h1' => 'Отзывы',
        ],
        'faq' => [
            'title' => 'Вопросы и ответы — {site_name}',
            'description' => 'Ответы на частые вопросы об аренде у {site_name}.',
            'h1' => 'Вопросы и ответы',
        ],
        'about' => [
            'title' => 'О компании — {site_name}',
            'description' => 'Информация о {site_name}.',
            'h1' => 'О компании',
        ],
        'delivery.anapa' => [
            'title' => 'Доставка в Анапу — {site_name}',
            'description' => 'Условия доставки техники в Анапу.',
            'h1' => 'Доставка в Анапу',
        ],
        'delivery.gelendzhik' => [
            'title' => 'Доставка в Геленджик — {site_name}',
            'description' => 'Условия доставки техники в Геленджик.',
            'h1' => 'Доставка в Геленджик',
        ],
        'motorcycle.show' => [
            'title' => '{motorcycle_name} — аренда — {site_name}',
            'description' => 'Аренда {motorcycle_name} у {site_name}. Характеристики и бронирование.',
            'h1' => '{motorcycle_name}',
        ],
        'booking.index' => [
            'title' => 'Бронирование — {site_name}',
            'description' => 'Выберите модель и даты бронирования у {site_name}.',
            'h1' => 'Бронирование',
        ],
        'booking.show' => [
            'title' => 'Бронирование {motorcycle_name} — {site_name}',
            'description' => 'Оформите бронирование {motorcycle_name} у {site_name}.',
            'h1' => 'Бронирование: {motorcycle_name}',
        ],
        'booking.checkout' => [
            'title' => 'Оформление — {site_name}',
            'description' => 'Завершение бронирования у {site_name}.',
            'h1' => 'Оформление',
        ],
        'booking.thank-you' => [
            'title' => 'Спасибо за заявку — {site_name}',
            'description' => 'Ваша заявка принята. Мы свяжемся с вами.',
            'h1' => 'Спасибо',
        ],
        'articles.index' => [
            'title' => 'Статьи — {site_name}',
            'description' => 'Полезные материалы и новости {site_name}.',
            'h1' => 'Статьи',
        ],
        'page.show' => [
            'title' => '{page_name} — {site_name}',
            'description' => '{page_name} — информация на сайте {site_name}.',
            'h1' => '{page_name}',
        ],
    ],
];
