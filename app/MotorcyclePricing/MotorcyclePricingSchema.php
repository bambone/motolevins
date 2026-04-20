<?php

declare(strict_types=1);

namespace App\MotorcyclePricing;

final class MotorcyclePricingSchema
{
    public const PROFILE_VERSION = 1;

    public const SNAPSHOT_VERSION = 1;

    /** ISO-4217 default when tenant has no separate currency on profile */
    public const DEFAULT_CURRENCY = 'RUB';

    /** Step between successive tariff `priority` / `sort_order` values after normalization (list order). */
    public const TARIFF_ORDER_STEP = 10;

    public static function orderValueForIndex(int $index): int
    {
        return ($index + 1) * self::TARIFF_ORDER_STEP;
    }
}
