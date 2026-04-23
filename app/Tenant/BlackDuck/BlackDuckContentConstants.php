<?php

declare(strict_types=1);

namespace App\Tenant\BlackDuck;

use Database\Seeders\Tenant\BlackDuckBootstrap;

/**
 * Freeze-лист Q1: каноничные публичные данные и внешние ссылки (источник для refresh-команд и bootstrap).
 *
 * @see BlackDuckContentRefresher
 * @see BlackDuckBootstrap
 */
final class BlackDuckContentConstants
{
    /** E.164 / форма: +7 (912) 305-00-15 */
    public const PHONE_DISPLAY = '+7 (912) 305-00-15';

    /** Рабочий ящик для заявок; перед продом подтвердите у клиента (был опечаточный вариант blackdack…). */
    public const EMAIL = 'blackduckdetailing@gmail.com';

    public const ADDRESS_PUBLIC = 'ул. Артиллерийская, 117/10, Тракторозаводский район, Челябинск, 454007';

    /** Кратко для настроек и JSON-LD city block */
    public const ADDRESS_CITY = 'Челябинск';

    /** Текстовый график (без выдуманного schema openingHours) */
    public const HOURS_TEXT = 'Пн–вс: с 9:00 (запись заранее, время закрытия уточняйте у менеджера).';

    public const CANONICAL_PUBLIC_BASE_URL = 'https://blackduck.rentbase.su';

    public const URL_2GIS = 'https://2gis.ru/chelyabinsk/inside/2111698024786654/query/%D0%B4%D0%B5%D1%82%D0%B5%D0%B9%D0%BB%D0%B8%D0%BD%D0%B3/firm/70000001053335703';

    public const URL_YANDEX_MAPS = 'https://yandex.ru/maps/org/black_duck_detailing/13151504118/?ll=61.436037%2C55.162689&z=15';

    /** Профиль Instagram; пусто — не выводим в контактах/отзывах/JSON-LD (главная instagram.com не подставляем). */
    public const URL_INSTAGRAM = '';

    /** Подставьте ссылку на сообщество VK; пусто — кнопку не показываем из плейсхолдера. */
    public const URL_VK = '';

    public const TELEGRAM_HANDLE = 'blackduckdetailing';

    public const THEME_KEY = 'black_duck';

    public const SLUGS = ['blackduck', 'black-duck'];

    /** Логотип на диске тенанта после import-assets */
    public const LOGO_LOGICAL = 'site/brand/logo.jpg';

    public const SETTING_FINGERPRINT_KEY = 'black_duck.content_refresh_fingerprint';

    /** {@see general.site_name} */
    public const PUBLIC_SITE_NAME = 'Black Duck Detailing';

    /** {@see general.short_description} */
    public const PUBLIC_SHORT_DESCRIPTION = 'Детейлинг-центр в Челябинске: PPF, керамика, тонировка, винил, химчистка, полировка.';

    /** {@see general.footer_tagline} — строка под копирайтом в минимальном подвале */
    public const PUBLIC_FOOTER_TAGLINE = 'Запись и согласование сложных работ; быстрые услуги — по слотам в расписании.';

    /**
     * Матрица Q1: slug существующего лендинга (или #) + подписи для hub.
     * booking_mode / price_mode — дескрипторы для согласованности копирайта, не BookableService slugs.
     *
     * @return list<array{slug: string, title: string, blurb: string, booking_mode: string, has_landing: bool}>
     */
    public static function serviceMatrixQ1(): array
    {
        return [
            ['slug' => 'detejling-mojka', 'title' => 'Детейлинг-мойка', 'blurb' => 'Короткие слоты онлайн после настройки расписания.', 'booking_mode' => 'instant', 'has_landing' => true],
            ['slug' => 'himchistka-salona', 'title' => 'Химчистка салона', 'blurb' => 'Салон, кожа, текстиль — объём по осмотру.', 'booking_mode' => 'confirm', 'has_landing' => true],
            ['slug' => 'polirovka-kuzova', 'title' => 'Полировка кузова', 'blurb' => 'По состоянию ЛКП, абразив и финиш по задаче.', 'booking_mode' => 'confirm', 'has_landing' => true],
            ['slug' => 'keramika', 'title' => 'Керамическое покрытие', 'blurb' => 'Серия этапов, согласование плана с мастером.', 'booking_mode' => 'confirm', 'has_landing' => true],
            ['slug' => 'ppf', 'title' => 'Антигравий PPF', 'blurb' => 'Защита зон кузова, макет и зоны — по осмотру.', 'booking_mode' => 'confirm', 'has_landing' => true],
            ['slug' => 'tonirovka', 'title' => 'Тонировка / оптика', 'blurb' => 'Стёкла и оптика, варианты плёнки — по согласованию.', 'booking_mode' => 'confirm', 'has_landing' => true],
            ['slug' => 'shumka', 'title' => 'Шумоизоляция', 'blurb' => 'Объём и сроки после диагностики.', 'booking_mode' => 'confirm', 'has_landing' => true],
            ['slug' => 'pdr', 'title' => 'PDR', 'blurb' => 'Вмятины без окраса — оценка на месте.', 'booking_mode' => 'confirm', 'has_landing' => true],
            ['slug' => 'predprodazhnaya', 'title' => 'Предпродажная подготовка', 'blurb' => 'Комплекс под продажу по чек-листу.', 'booking_mode' => 'confirm', 'has_landing' => true],
            ['slug' => '#expert-inquiry', 'title' => 'Виниловая оклейка', 'blurb' => 'Проекты по дизайну; расчёт и сроки — заявка.', 'booking_mode' => 'quote', 'has_landing' => false],
        ];
    }

    /**
     * Проверка «ещё плейсхолдер bootstrap», чтобы --if-placeholder не затирал ручной контент.
     */
    public static function looksLikeBootstrapPhone(string $v): bool
    {
        return str_contains($v, '(351) 200-00-00') || str_contains($v, '351) 200');
    }

    public static function looksLikePlaceholderEmail(string $v): bool
    {
        return $v === '' || $v === 'hello@example.local';
    }

    /**
     * URL Instagram для публичного вывода: не пустой и не «голая» главная instagram.com.
     */
    public static function instagramUrlForPublic(): string
    {
        $u = trim(self::URL_INSTAGRAM);
        if ($u === '') {
            return '';
        }
        $normalized = rtrim(strtolower($u), '/').'/';
        $genericRoots = [
            'https://www.instagram.com/',
            'https://instagram.com/',
            'http://www.instagram.com/',
            'http://instagram.com/',
        ];
        foreach ($genericRoots as $g) {
            if ($normalized === $g) {
                return '';
            }
        }

        return rtrim($u, '/');
    }

    public static function taglineLong(): string
    {
        return 'Доверяйте свой автомобиль только профессионалам. Комплекс услуг по изменению внешнего вида и уходу: винил, PPF, тонировка и оптика, кожа и салон, полировка, керамика, химчистка, предпродажа. '
            .'Консультация и честные рекомендации с опытом — под задачу и реальное состояние ЛКП и салона.';
    }
}
