<?php

declare(strict_types=1);

namespace App\Services\Reviews\Imports;

final class ReviewSourceDetector
{
    public static function providerFromUrl(string $url): string
    {
        $u = mb_strtolower(trim($url));

        if (str_contains($u, 'vk.com') && str_contains($u, 'topic-')) {
            return 'vk_topic';
        }
        if (str_contains($u, 'vk.com') && (str_contains($u, 'wall-') || str_contains($u, '/wall'))) {
            return 'vk_wall';
        }
        if (str_contains($u, '2gis.ru') || str_contains($u, '2gis.com')) {
            return 'two_gis';
        }
        if (str_contains($u, 'yandex.ru/maps') || str_contains($u, 'yandex.com/maps')) {
            return 'yandex_maps';
        }

        return 'manual';
    }
}
