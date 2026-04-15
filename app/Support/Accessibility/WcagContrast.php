<?php

declare(strict_types=1);

namespace App\Support\Accessibility;

/**
 * WCAG 2.x relative luminance and contrast ratio for sRGB hex colors (#RGB / #RRGGBB).
 */
final class WcagContrast
{
    /**
     * Contrast ratio between two sRGB colors (order-independent).
     */
    public static function ratio(string $hexA, string $hexB): ?float
    {
        $la = self::relativeLuminance($hexA);
        $lb = self::relativeLuminance($hexB);
        if ($la === null || $lb === null) {
            return null;
        }

        $lighter = max($la, $lb);
        $darker = min($la, $lb);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    public static function relativeLuminance(string $hex): ?float
    {
        $rgb = self::parseHexRgb($hex);
        if ($rgb === null) {
            return null;
        }

        $r = self::linearizeChannel($rgb[0]);
        $g = self::linearizeChannel($rgb[1]);
        $b = self::linearizeChannel($rgb[2]);

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * @return array{0: int, 1: int, 2: int}|null
     */
    private static function parseHexRgb(string $hex): ?array
    {
        $hex = strtolower(ltrim(trim($hex), '#'));
        if ($hex === '') {
            return null;
        }

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return null;
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private static function linearizeChannel(int $c): float
    {
        $v = $c / 255.0;

        return $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
    }
}
