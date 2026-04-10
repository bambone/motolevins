<?php

namespace App\Services\Seo;

use App\Models\Tenant;

/**
 * BCP-47 value for tenant public HTML {@code lang} attribute.
 *
 * Source of truth: {@see Tenant::$locale} when non-empty, else {@see config('app.locale')}.
 */
final class TenantPublicHtmlLang
{
    public static function attribute(?Tenant $tenant): string
    {
        $raw = '';
        if ($tenant !== null) {
            $raw = trim((string) $tenant->locale);
        }
        if ($raw === '') {
            $raw = trim((string) config('app.locale', 'en'));
        }

        return self::toBcp47($raw);
    }

    /**
     * Normalize ru, ru_RU, ru-RU, en_US → BCP-47 (hyphens, correct casing).
     */
    public static function toBcp47(string $raw): string
    {
        $raw = trim(str_replace('_', '-', $raw));
        if ($raw === '') {
            return 'en';
        }

        $segments = array_values(array_filter(
            explode('-', $raw),
            static fn (string $s): bool => $s !== '',
        ));
        if ($segments === []) {
            return 'en';
        }

        $out = [];
        $out[] = strtolower($segments[0]);
        for ($i = 1, $n = count($segments); $i < $n; $i++) {
            $s = $segments[$i];
            if (strlen($s) === 2 && ctype_alpha($s)) {
                $out[] = strtoupper($s);
            } elseif (strlen($s) === 4 && ctype_alpha($s)) {
                $out[] = ucfirst(strtolower($s));
            } else {
                $out[] = strtolower($s);
            }
        }

        return implode('-', $out);
    }
}
