<?php

namespace App\Services\Seo;

/**
 * Per-field merge helpers: empty string, null, whitespace-only = missing.
 */
final class TenantSeoMerge
{
    public static function isFilled(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        return trim($value) !== '';
    }

    /**
     * @param  list<string|null>  $candidates  first wins if filled
     */
    public static function firstFilled(?string ...$candidates): ?string
    {
        foreach ($candidates as $c) {
            if (self::isFilled($c)) {
                return trim((string) $c);
            }
        }

        return null;
    }
}
