<?php

namespace App\ContactChannels;

/**
 * Нормализация ввода посетителя в машинный value для JSON (value = нормализованное).
 */
final class VisitorContactNormalizer
{
    public static function normalizeTelegram(string $raw): ?string
    {
        $s = trim($raw);
        if ($s === '') {
            return null;
        }

        if (preg_match('#^(?:https?://)?(?:t\.me|telegram\.me)/([a-zA-Z0-9_]+)#i', $s, $m)) {
            return strtolower($m[1]);
        }

        $s = ltrim($s, '@');

        return preg_match('/^[a-zA-Z0-9_]{5,32}$/', $s) ? strtolower($s) : null;
    }

    /**
     * В value всегда канонический https URL профиля VK.
     */
    public static function normalizeVk(string $raw): ?string
    {
        $s = trim($raw);
        if ($s === '') {
            return null;
        }

        if (preg_match('#^https?://(?:m\.)?vk\.com/([a-zA-Z0-9._-]+)/?#i', $s, $m)) {
            $id = $m[1];

            return 'https://vk.com/'.$id;
        }

        if (preg_match('#^vk\.com/([a-zA-Z0-9._-]+)#i', $s, $m)) {
            return 'https://vk.com/'.$m[1];
        }

        if (preg_match('/^[a-zA-Z0-9._-]{2,}$/', $s) && ! str_contains($s, '://')) {
            return 'https://vk.com/'.$s;
        }

        return null;
    }

    /**
     * MVP: непустая строка или URL; без привязки к телефону.
     */
    public static function normalizeMax(string $raw): ?string
    {
        $s = trim($raw);
        if ($s === '') {
            return null;
        }

        if (filter_var($s, FILTER_VALIDATE_URL)) {
            return $s;
        }

        if (strlen($s) >= 2 && strlen($s) <= 500) {
            return $s;
        }

        return null;
    }
}
