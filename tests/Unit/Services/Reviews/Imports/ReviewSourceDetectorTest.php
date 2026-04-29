<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Reviews\Imports;

use App\Services\Reviews\Imports\ReviewSourceDetector;
use PHPUnit\Framework\TestCase;

final class ReviewSourceDetectorTest extends TestCase
{
    public function test_detects_vk_topic(): void
    {
        $this->assertSame(
            'vk_topic',
            ReviewSourceDetector::providerFromUrl('https://vk.com/topic-123_456'),
        );
    }

    public function test_detects_vk_wall(): void
    {
        $this->assertSame('vk_wall', ReviewSourceDetector::providerFromUrl('https://vk.com/wall-1_2'));
        $this->assertSame('vk_wall', ReviewSourceDetector::providerFromUrl('https://vk.com/club1?w=wall-1_2'));
    }

    public function test_detects_two_gis_and_yandex_maps(): void
    {
        $this->assertSame('two_gis', ReviewSourceDetector::providerFromUrl('https://2gis.ru/moscow'));
        $this->assertSame('two_gis', ReviewSourceDetector::providerFromUrl('https://2gis.com/foo'));
        $this->assertSame('yandex_maps', ReviewSourceDetector::providerFromUrl('https://yandex.ru/maps/org/1'));
        $this->assertSame('yandex_maps', ReviewSourceDetector::providerFromUrl('https://yandex.com/maps/foo'));
    }

    public function test_unknown_url_is_manual(): void
    {
        $this->assertSame('manual', ReviewSourceDetector::providerFromUrl('https://example.com/reviews'));
    }
}
