<?php

namespace App\Support\Phone;

/**
 * Нормализация и валидация телефона (E.164-подобно: + и только цифры).
 * Зеркалит правила {@see resources/js/tenant-intl-phone.js}.
 */
class IntlPhoneNormalizer
{
    /**
     * @return list<array{key: string, code: string, national_min: int, national_max: int, priority: int, example: string}>
     */
    public static function countries(): array
    {
        return config('intl_phone', []);
    }

    /**
     * Удаление мусора, tel:, префикса 00.
     */
    public static function sanitizePhoneInput(?string $raw): string
    {
        if ($raw === null) {
            return '';
        }

        return trim(preg_replace('/^tel:/i', '', trim($raw)) ?? '');
    }

    /**
     * Все цифры подряд (макс. 15 — лимит E.164 на значимые цифры).
     */
    public static function digitsOnly(string $s): string
    {
        $d = preg_replace('/\D+/', '', $s) ?? '';

        return strlen($d) > 15 ? substr($d, 0, 15) : $d;
    }

    /**
     * @return list<array{key: string, code: string, national_min: int, national_max: int, priority: int, example: string}>
     */
    public static function countriesSortedByCodeLength(): array
    {
        $list = self::countries();
        usort($list, function ($a, $b) {
            $la = strlen($a['code']);
            $lb = strlen($b['code']);
            if ($la !== $lb) {
                return $lb <=> $la;
            }

            return ($b['priority'] ?? 0) <=> ($a['priority'] ?? 0);
        });

        return $list;
    }

    /**
     * Подбор кода страны по префиксу цифр (после +).
     *
     * @return array{key: string, code: string, national_min: int, national_max: int}|null
     */
    public static function detectCountryByDigits(string $digitsWithCountry): ?array
    {
        foreach (self::countriesSortedByCodeLength() as $row) {
            $c = $row['code'];
            if ($c !== '' && str_starts_with($digitsWithCountry, $c)) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Нормализует произвольный ввод в строку вида +[цифры] (до 15 цифр после +).
     */
    public static function normalizePhone(?string $raw, string $defaultCountry = 'RU'): string
    {
        $s = self::sanitizePhoneInput($raw);
        if ($s === '') {
            return '';
        }

        $hadPlus = str_contains($s, '+');
        $had00 = (bool) preg_match('/^\s*\+?\s*00/', $s);

        $d = self::digitsOnly($s);
        if ($had00 && str_starts_with($d, '00')) {
            $d = substr($d, 2);
        }

        if ($d === '') {
            return $hadPlus ? '+' : '';
        }

        if ($hadPlus || $had00) {
            return '+'.substr($d, 0, 15);
        }

        // РФ по умолчанию (без +)
        if ($d[0] === '8') {
            $d = '7'.substr($d, 1);
        }

        if (strlen($d) === 11 && $d[0] === '7') {
            return '+'.substr($d, 0, 15);
        }

        // NANP без плюса: 11 цифр, начинается с 1
        if (strlen($d) === 11 && $d[0] === '1') {
            return '+'.substr($d, 0, 15);
        }

        // 10 цифр без префикса — по умолчанию РФ (код 7)
        if (strlen($d) === 10) {
            $d = '7'.$d;
        }

        if ($d[0] === '7') {
            return '+'.substr($d, 0, 15);
        }

        return '+'.substr($d, 0, 15);
    }

    public static function validatePhone(string $normalized): bool
    {
        if ($normalized === '' || $normalized === '+') {
            return false;
        }

        if (! preg_match('/^\+[1-9]\d{6,14}$/', $normalized)) {
            return false;
        }

        $digits = substr($normalized, 1);
        $country = self::detectCountryByDigits($digits);

        if ($country === null) {
            return strlen($digits) >= 8 && strlen($digits) <= 15;
        }

        $code = $country['code'];
        $national = substr($digits, strlen($code));
        $n = strlen($national);

        return $n >= $country['national_min'] && $n <= $country['national_max'];
    }
}
