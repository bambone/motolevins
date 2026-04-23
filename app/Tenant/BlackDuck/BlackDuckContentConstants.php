<?php

declare(strict_types=1);

namespace App\Tenant\BlackDuck;

use Database\Seeders\Tenant\BlackDuckBootstrap;

/**
 * Freeze-лист Q1: каноничные публичные данные и внешние ссылки (источник для refresh-команд и bootstrap).
 *
 * Медиа: Яндекс.Карты/внешние каталоги — только этап отбора. На сайт попадают оптимизированные файлы
 * (curated-export) и пути из {@see \App\Tenant\BlackDuck\BlackDuckMediaCatalog}, не внешние URL в src.
 *
 * @see BlackDuckContentRefresher
 * @see BlackDuckBootstrap
 * @see BlackDuckMediaCatalog
 * @see BlackDuckServiceRegistry
 */
final class BlackDuckContentConstants
{
    /** E.164 / форма: +7 (912) 305-00-15 */
    public const PHONE_DISPLAY = '+7 (912) 305-00-15';

    /**
     * Рабочий ящик для заявок и {@see BlackDuckContentRefresher}: канон для настроек тенанта.
     * Перед релизом сверить с заказчиком относительно {@see self::EMAIL_AS_LISTED_ON_2GIS}.
     */
    public const EMAIL = 'blackduckdetailing@gmail.com';

    /**
     * Вариант, который может отображаться в карточке 2ГИС; не подставляется в код автоматически.
     * Чеклист: выбрать один канонический ящик с бизнесом и обновить {@see self::EMAIL} при необходимости.
     */
    public const EMAIL_AS_LISTED_ON_2GIS = 'blackdackdetailing@gmail.com';

    public const ADDRESS_PUBLIC = 'ул. Артиллерийская, 117/10, Тракторозаводский район, Челябинск, 454007';

    /** Кратко для настроек и JSON-LD city block */
    public const ADDRESS_CITY = 'Челябинск';

    /** Текстовый график (без выдуманного schema openingHours) */
    public const HOURS_TEXT = 'Пн–вс: с 9:00 (запись заранее, время закрытия уточняйте у менеджера).';

    public const CANONICAL_PUBLIC_BASE_URL = 'https://blackduck.rentbase.su';

    /**
     * Основной CTA «запись / расчёт»: отдельная страница, форма contact_inquiry (не встроенный expert_lead внизу лендинга).
     */
    public const PRIMARY_LEAD_URL = '/contacts#contact-inquiry';

    /** Вторичный CTA с home: визуальное портфолио на /raboty (не lead-форма). */
    public const WORKS_PAGE_URL = '/raboty';

    /**
     * Форма заявки на /contacts с привязкой к посадочной услуге (slug страницы).
     */
    public static function contactsInquiryUrlForServiceSlug(string $servicePageSlug): string
    {
        $s = trim($servicePageSlug);
        if ($s === '' || str_starts_with($s, '#')) {
            return self::PRIMARY_LEAD_URL;
        }

        return '/contacts?service='.rawurlencode($s).'#contact-inquiry';
    }

    /**
     * URL посадочной с флагом записи: на странице услуги показываем подсказку и ведём на форму с {@see contactsInquiryUrlForServiceSlug()}.
     */
    public static function serviceLandingBookIntentUrl(string $servicePageSlug): string
    {
        $s = trim($servicePageSlug);
        if ($s === '' || str_starts_with($s, '#')) {
            return self::PRIMARY_LEAD_URL;
        }

        return '/'.$s.'?book=1';
    }

    /**
     * Slug витринных карточек на home (6–8), без полного каталога. Порядок = порядок на главной.
     * Включает только сущности с лендингом или осмысленный CTA на заявку.
     *
     * @var list<string>
     */
    public const HOME_SERVICE_PREVIEW_SLUGS = [
        'ppf',
        'keramika',
        'himchistka-salona',
        'polirovka-kuzova',
        'detejling-mojka',
        'tonirovka',
        'pdr',
        'predprodazhnaya',
    ];

    public const URL_2GIS = 'https://2gis.ru/chelyabinsk/inside/2111698024786654/query/%D0%B4%D0%B5%D1%82%D0%B5%D0%B9%D0%BB%D0%B8%D0%BD%D0%B3/firm/70000001053335703';

    /** Вкладка отзывов (для ссылок «ещё на карте»). */
    public const URL_2GIS_REVIEWS_TAB = 'https://2gis.ru/chelyabinsk/firm/70000001053335703/tab/reviews';

    public const URL_YANDEX_MAPS = 'https://yandex.ru/maps/org/black_duck_detailing/13151504118/?ll=61.436037%2C55.162689&z=15';

    public const URL_YANDEX_MAPS_REVIEWS_TAB = 'https://yandex.ru/maps/org/black_duck_detailing/13151504118/reviews/';

    /** Профиль Instagram; пусто — не выводим в контактах/отзывах/JSON-LD (главная instagram.com не подставляем). */
    public const URL_INSTAGRAM = '';

    /** Подставьте ссылку на сообщество VK; пусто — кнопку не показываем из плейсхолдера. */
    public const URL_VK = '';

    public const TELEGRAM_HANDLE = 'blackduckdetailing';

    public const THEME_KEY = 'black_duck';

    /**
     * Канонический id на production (R2/БД/пути {@code tenants/{id}/...}); локал приводить командой rekey, см. {@see \App\Console\Commands\TenantRekeyIdCommand}.
     */
    public const CANONICAL_TENANT_ID = 4;

    public const SLUGS = ['blackduck', 'black-duck'];

    /** Логотип на диске тенанта после import-assets */
    public const LOGO_LOGICAL = 'site/brand/logo.jpg';

    /**
     * Базовое имя (без расширения) единого фона hero посадочных услуг: {@code site/brand/service-landing-hero.png}.
     * Импорт: {@see \App\Console\Commands\BlackDuckImportServiceLandingHeroCommand}; приоритет выше, чем картинка из матрицы услуги.
     */
    public const SERVICE_LANDING_HEADER_STEM = 'site/brand/service-landing-hero';

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
    /**
     * Подмножество {@see serviceMatrixQ1()} для превью на главной: 6–8 сильных направлений.
     *
     * @return list<array{slug: string, title: string, blurb: string, booking_mode: string, has_landing: bool}>
     */
    public static function serviceMatrixHomePreview(): array
    {
        $bySlug = [];
        foreach (self::serviceMatrixQ1() as $row) {
            $bySlug[(string) $row['slug']] = $row;
        }
        $out = [];
        foreach (self::HOME_SERVICE_PREVIEW_SLUGS as $slug) {
            $reg = BlackDuckServiceRegistry::rowBySlug($slug);
            if ($reg !== null && ! ($reg['show_on_home'] ?? false)) {
                continue;
            }
            if (isset($bySlug[$slug])) {
                $out[] = $bySlug[$slug];
            }
        }
        if ($out === []) {
            foreach (BlackDuckServiceRegistry::all() as $reg) {
                $row = [
                    'slug' => $reg['slug'],
                    'title' => $reg['title'],
                    'blurb' => $reg['blurb'],
                    'booking_mode' => $reg['booking_mode'],
                    'has_landing' => $reg['has_landing'],
                ];
                if (str_starts_with((string) $row['slug'], '#')) {
                    continue;
                }
                if (! ($reg['show_on_home'] ?? false)) {
                    continue;
                }
                $out[] = $row;
                if (count($out) >= 8) {
                    break;
                }
            }
        }

        return $out;
    }

    /**
     * Короткий подзаголовок для home-карточки (маркетинг), если отличается от blurb.
     * Ключ — slug из {@see serviceMatrixQ1()}.
     *
     * @return array<string, string>
     */
    public static function homeServiceCardPreviewSubtitlesBySlug(): array
    {
        return [
            'ppf' => 'Защита ЛКП: зоны и кромка под задачу.',
            'keramika' => 'Глянец и уход: план с мастером.',
            'himchistka-salona' => 'Салон и материалы — после осмотра.',
            'polirovka-kuzova' => 'Абразив и финиш по состоянию ЛКП.',
            'detejling-mojka' => 'Быстрые слоты, когда расписание включено.',
            'tonirovka' => 'Комфорт и внешний вид стёкол/оптики.',
            'pdr' => 'Вмятины без окраса — оценка на месте.',
            'predprodazhnaya' => 'Внешний вид под осмотр покупателя.',
        ];
    }

    public static function serviceTitleForSlug(string $slug): string
    {
        return BlackDuckServiceRegistry::serviceTitleForSlug($slug);
    }

    /**
     * @return list<array{slug: string, title: string, blurb: string, booking_mode: string, has_landing: bool}>
     */
    public static function serviceMatrixQ1(): array
    {
        return BlackDuckServiceRegistry::legacyMatrixQ1();
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
        return 'Детейлинг в Челябинске: аккуратная работа с ЛКП и салоном, честный объём и сроки. PPF, керамика, тонировка и оптика, химчистка и кожа, полировка, предпродажа и винил по заявке. '
            .'Осмотр и понятная смета до старта сложных работ — без громких обещаний, с упором на аккуратный визуальный результат.';
    }
}
