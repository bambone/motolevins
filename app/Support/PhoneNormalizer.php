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
     * Человекочитаемый номер для UI: полный номер, без обрезки многоточием в типичных кейсах.
     * Лишние цифры после 11-значного RU — отдельной группой (оператор видит всё).
     */
    public static function formatForDisplay(?string $phone): string
    {
        if ($phone === null || trim($phone) === '') {
            return '—';
        }

        $raw = trim($phone);
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if ($digits === '') {
            return $raw;
        }

        if (strlen($digits) >= 11 && $digits[0] === '8') {
            $digits = '7'.substr($digits, 1);
        }

        if (strlen($digits) >= 11 && $digits[0] === '7') {
            $head = substr($digits, 0, 11);
            $n10 = substr($head, 1);

            $formatted = sprintf(
                '+7 %s %s-%s-%s',
                substr($n10, 0, 3),
                substr($n10, 3, 3),
                substr($n10, 6, 2),
                substr($n10, 8, 2),
            );

            if (strlen($digits) > 11) {
                $extra = substr($digits, 11);
                $extraGrouped = trim(chunk_split($extra, 3, ' '));

                return $formatted.' · '.$extraGrouped;
            }

            return $formatted;
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            return sprintf(
                '+7 %s %s-%s-%s',
                substr($digits, 0, 3),
                substr($digits, 3, 3),
                substr($digits, 6, 2),
                substr($digits, 8, 2),
            );
        }

        return trim(chunk_split($digits, 3, ' '));
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
