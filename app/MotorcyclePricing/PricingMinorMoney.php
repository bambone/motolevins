<?php

declare(strict_types=1);

namespace App\MotorcyclePricing;

/**
 * Converts tenant DB "major" integers (whole rubles) to profile/snapshot minor (kopeks).
 */
final class PricingMinorMoney
{
    public const MINOR_PER_MAJOR = 100;

    public static function majorToMinor(int $major): int
    {
        return $major * self::MINOR_PER_MAJOR;
    }

    public static function minorToMajor(int $minor): int
    {
        return intdiv($minor, self::MINOR_PER_MAJOR);
    }
}
