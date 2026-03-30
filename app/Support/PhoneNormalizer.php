<?php

namespace App\Support;

use App\Models\Booking;

final class PhoneNormalizer
{
    public static function normalize(string $phone): string
    {
        return preg_replace('/[^0-9+]/', '', $phone) ?? '';
    }

    public static function normalizeOrEmpty(?string $phone): string
    {
        if ($phone === null || trim($phone) === '') {
            return '';
        }

        return self::normalize($phone);
    }

    /**
     * Варианты для сравнения с {@see Booking::phone_normalized} (например 8… и +7…).
     *
     * @return list<string>
     */
    public static function comparisonVariants(string $normalized): array
    {
        $out = [$normalized];
        $digits = preg_replace('/\D/', '', $normalized) ?? '';

        if (strlen($digits) === 11 && str_starts_with($digits, '8')) {
            $out[] = '+7'.substr($digits, 1);
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '7')) {
            $out[] = '+7'.substr($digits, 1);
        }

        if (str_starts_with($normalized, '+7') && strlen($digits) >= 11) {
            $rest = substr($digits, -10);
            $out[] = '8'.$rest;
        }

        return array_values(array_unique(array_filter($out)));
    }
}
