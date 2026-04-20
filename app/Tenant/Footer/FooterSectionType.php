<?php

declare(strict_types=1);

namespace App\Tenant\Footer;

/**
 * Типизированные секции подвала v1 (контракт meta_json в {@see FooterSectionMetaValidator}).
 */
final class FooterSectionType
{
    public const CTA_STRIP = 'cta_strip';

    public const CONTACTS = 'contacts';

    public const GEO_POINTS = 'geo_points';

    public const CONDITIONS_LIST = 'conditions_list';

    public const LINK_GROUPS = 'link_groups';

    public const BOTTOM_BAR = 'bottom_bar';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::CTA_STRIP,
            self::CONTACTS,
            self::GEO_POINTS,
            self::CONDITIONS_LIST,
            self::LINK_GROUPS,
            self::BOTTOM_BAR,
        ];
    }

    public static function label(string $type): string
    {
        return match ($type) {
            self::CTA_STRIP => 'Финальный призыв (CTA)',
            self::CONTACTS => 'Контакты (каналы из настроек)',
            self::GEO_POINTS => 'География / точки выдачи',
            self::CONDITIONS_LIST => 'Краткие условия аренды',
            self::LINK_GROUPS => 'Группы ссылок',
            self::BOTTOM_BAR => 'Нижняя строка (копирайт)',
            default => $type,
        };
    }

    /**
     * Максимум активных секций данного type на тенанта (v1).
     */
    public static function maxPerType(string $type): int
    {
        return match ($type) {
            self::LINK_GROUPS => 3,
            default => 1,
        };
    }
}
