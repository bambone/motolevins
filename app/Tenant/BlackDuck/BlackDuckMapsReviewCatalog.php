<?php

declare(strict_types=1);

namespace App\Tenant\BlackDuck;

use Illuminate\Support\Str;

/**
 * Курируемые отзывы с публичных карт (2ГИС, Яндекс) для посадочных услуг Black Duck.
 * Уникальные авторы в пуле; на каждую посадочную — {@see REVIEWS_PER_LANDING} карточек + CTA на карты в шаблоне.
 *
 * @phpstan-type PoolRow array{
 *   name: string,
 *   city: string,
 *   platform: '2gis'|'yandex',
 *   text: string,
 *   avatar: ?string,
 *   headline?: string
 * }
 */
final class BlackDuckMapsReviewCatalog
{
    public const SOURCE = 'maps_curated';

    /** Максимум карточек отзыва на посадочной; фактическое число может быть 3–4, если пул короче. */
    public const REVIEWS_PER_LANDING = 4;

    /** @return list<string> */
    public static function landingSlugOrder(): array
    {
        $out = [];
        foreach (BlackDuckServiceRegistry::all() as $r) {
            if (! $r['has_landing'] || str_starts_with((string) $r['slug'], '#')) {
                continue;
            }
            $out[] = (string) $r['slug'];
        }

        return $out;
    }

    /**
     * @return list<PoolRow>
     */
    public static function pool(): array
    {
        $phpPath = database_path('data/black_duck_maps_reviews_pool.php');
        if (is_readable($phpPath)) {
            $loaded = require $phpPath;
            $normalized = self::normalizePoolRows(is_array($loaded) ? $loaded : []);
            if ($normalized !== []) {
                return $normalized;
            }
        }

        $path = database_path('data/black_duck_maps_reviews_pool.json');
        if (! is_readable($path)) {
            return self::fallbackPool();
        }
        $raw = json_decode((string) file_get_contents($path), true);
        if (! is_array($raw)) {
            return self::fallbackPool();
        }
        $out = self::normalizePoolRows($raw);

        return $out !== [] ? $out : self::fallbackPool();
    }

    /**
     * @param  list<mixed>  $raw
     * @return list<PoolRow>
     */
    private static function normalizePoolRows(array $raw): array
    {
        $out = [];
        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? ''));
            $text = trim((string) ($row['text'] ?? ''));
            if ($name === '' || $text === '') {
                continue;
            }
            $platform = (string) ($row['platform'] ?? '2gis');
            if ($platform !== 'yandex' && $platform !== '2gis') {
                $platform = '2gis';
            }
            /** @var '2gis'|'yandex' $p */
            $p = $platform === 'yandex' ? 'yandex' : '2gis';
            $avatar = isset($row['avatar']) && is_string($row['avatar']) && $row['avatar'] !== '' ? $row['avatar'] : null;
            $headline = isset($row['headline']) && is_string($row['headline']) ? trim($row['headline']) : '';
            $item = [
                'name' => $name,
                'city' => trim((string) ($row['city'] ?? 'Челябинск')) ?: 'Челябинск',
                'platform' => $p,
                'text' => $text,
                'avatar' => $avatar,
            ];
            if ($headline !== '') {
                $item['headline'] = $headline;
            }
            $out[] = $item;
        }

        return $out;
    }

    /**
     * Резерв, если JSON ещё не развёрнут на окружении.
     *
     * @return list<PoolRow>
     */
    private static function fallbackPool(): array
    {
        return [
            [
                'name' => 'Тимофей',
                'city' => 'Челябинск',
                'platform' => '2gis',
                'text' => 'Отличный сервис! Мастер Игорь устранил и остановил трещину на лобовом стекле. Работа выполнена аккуратно, профессионально и быстро.',
                'avatar' => null,
            ],
            [
                'name' => 'Ростислав Сухоруков',
                'city' => 'Челябинск',
                'platform' => 'yandex',
                'text' => 'Остался очень доволен: доброжелательные сотрудники, всегда на связи, проконсультировали по ценам и срокам. Всё сделали вовремя, машина стала как новая, без следов и запахов.',
                'avatar' => null,
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>> rows for reviews table insert
     */
    public static function rowsForDatabaseSeed(): array
    {
        $slugs = self::landingSlugOrder();
        $pool = self::pool();
        if ($pool === [] || $slugs === []) {
            return [];
        }

        $slugN = count($slugs);
        $poolN = count($pool);
        /** @var list<int> $counts reviews per landing */
        $counts = array_fill(0, $slugN, intdiv($poolN, $slugN));
        $rem = $poolN % $slugN;
        for ($i = 0; $i < $rem; $i++) {
            $counts[$i]++;
        }

        $now = now()->toDateString();
        $out = [];
        $idx = 0;
        foreach ($slugs as $si => $slug) {
            $take = $counts[$si];
            for ($k = 0; $k < $take; $k++) {
                if ($idx >= $poolN) {
                    break 2;
                }
                $row = $pool[$idx++];
                $meta = ['maps_platform' => $row['platform']];
                if (! empty($row['avatar']) && is_string($row['avatar'])) {
                    $meta['avatar_external_url'] = $row['avatar'];
                }
                $text = (string) $row['text'];
                $headline = trim((string) ($row['headline'] ?? ''));
                if ($headline === '') {
                    $headline = match ($row['platform']) {
                        'yandex' => 'Яндекс Карты',
                        default => '2ГИС',
                    };
                }
                $out[] = [
                    'name' => $row['name'],
                    'city' => $row['city'],
                    'headline' => $headline,
                    'text_short' => Str::limit($text, 220, '…'),
                    'text_long' => $text,
                    'text' => $text,
                    'rating' => 5,
                    'category_key' => $slug,
                    'source' => self::SOURCE,
                    'status' => 'published',
                    'is_featured' => false,
                    'sort_order' => ($k + 1) * 10,
                    'date' => $now,
                    'media_type' => 'text',
                    'meta_json' => $meta,
                ];
            }
        }

        return $out;
    }
}
