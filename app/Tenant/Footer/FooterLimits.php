<?php

declare(strict_types=1);

namespace App\Tenant\Footer;

/**
 * Лимиты v1 для подвала (админка + публичный резолвер).
 */
final class FooterLimits
{
    public const MAX_SECTIONS_TOTAL = 8;

    public const MAX_LINKS_PER_SECTION = 12;

    public const LINK_GROUPS_MAX_GROUPS = 4;

    public const LINK_GROUP_MAX_LINKS = 8;

    public const SHORT_FIELD_MAX = 120;

    public const LONG_FIELD_MAX = 300;

    public const LIST_ITEMS_MAX = 6;
}
