<?php

namespace App\Support;

/**
 * Приводит чипы каталога к ограниченному словарю и уникальным подписям (макс. 3).
 */
final class CatalogHighlightNormalizer
{
    /**
     * @return array<string, string> ключ => подпись для Filament Select
     */
    public static function selectOptions(): array
    {
        return config('motorcycle_catalog.canonical_labels', []);
    }

    public static function normalizeToKey(?string $raw): ?string
    {
        if (! filled($raw)) {
            return null;
        }

        $labels = config('motorcycle_catalog.canonical_labels', []);
        $aliases = config('motorcycle_catalog.aliases', []);

        return self::resolveKey(trim((string) $raw), $labels, $aliases);
    }

    /**
     * @param  array<int, string|null>  $candidates  ключи, алиасы или произвольный текст
     * @return array<int, string> до 3 уникальных подписей
     */
    public static function normalizeToLabels(array $candidates): array
    {
        $labels = config('motorcycle_catalog.canonical_labels', []);
        $seen = [];
        $out = [];

        foreach ($candidates as $raw) {
            $key = self::normalizeToKey($raw !== null ? (string) $raw : null);
            if ($key === null) {
                continue;
            }

            $label = $labels[$key] ?? null;
            if ($label === null || isset($seen[$label])) {
                continue;
            }

            $seen[$label] = true;
            $out[] = $label;

            if (count($out) >= 3) {
                break;
            }
        }

        return $out;
    }

    /**
     * @param  array<string, string>  $labels
     * @param  array<string, string>  $aliases
     */
    private static function resolveKey(string $trimmed, array $labels, array $aliases): ?string
    {
        if ($trimmed === '') {
            return null;
        }

        if (isset($labels[$trimmed])) {
            return $trimmed;
        }

        $asKey = mb_strtolower($trimmed);
        if (isset($labels[$asKey])) {
            return $asKey;
        }

        $lower = mb_strtolower($trimmed);

        return $aliases[$lower] ?? null;
    }
}
