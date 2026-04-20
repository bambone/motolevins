<?php

declare(strict_types=1);

namespace App\MotorcyclePricing;

/**
 * How a day-priced tariff is worded on the site (catalog/detail copy). Does not change quote math.
 */
enum TariffCatalogDayUnit: string
{
    /** Full 24h-style «сутки» / «за сутки». */
    case FullDay = 'full_day';

    /** Shorter «день» / «за день» (e.g. daytime package); use {@see self::hint} for «10 часов» etc. */
    case ShortDay = 'short_day';

    public static function fromProfile(mixed $raw): self
    {
        $v = is_string($raw) ? $raw : '';

        return $v === self::ShortDay->value ? self::ShortDay : self::FullDay;
    }

    /** Lowercase phrase after amount on cards/lists. */
    public function perUnitSuffix(): string
    {
        return match ($this) {
            self::FullDay => 'за сутки',
            self::ShortDay => 'за день',
        };
    }

    /**
     * Word after an N–M range, e.g. «1–3 суток» vs «1–3 дня».
     */
    public function rangeBucketWord(): string
    {
        return match ($this) {
            self::FullDay => 'суток',
            self::ShortDay => 'дня',
        };
    }
}
