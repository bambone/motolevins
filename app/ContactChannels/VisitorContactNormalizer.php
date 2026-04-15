<?php

namespace App\ContactChannels;

/**
 * Нормализация ввода посетителя в машинный value для JSON (value = нормализованное).
 */
final class VisitorContactNormalizer
{
    /**
     * Username Telegram: [a-zA-Z0-9_]{5,32} или t.me/… / telegram.me/… с тем же набором в сегменте.
     * Кириллица и прочие не-ASCII в нике не допускаются (как в Telegram).
     */
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
     *
     * Отклоняем неоднозначный ввод вроде «vk» (не идентификатор профиля).
     */
    public static function normalizeVk(string $raw): ?string
    {
        $s = trim($raw);
        if ($s === '') {
            return null;
        }

        if (preg_match('#^https?://(?:m\.)?vk\.com/([a-zA-Z0-9._-]+)/?#i', $s, $m)) {
            $id = $m[1];
            if (! self::isValidVkPathSegment($id)) {
                return null;
            }

            return 'https://vk.com/'.$id;
        }

        if (preg_match('#^vk\.com/([a-zA-Z0-9._-]+)#i', $s, $m)) {
            $id = $m[1];
            if (! self::isValidVkPathSegment($id)) {
                return null;
            }

            return 'https://vk.com/'.$id;
        }

        if (preg_match('/^[a-zA-Z0-9._-]{2,}$/', $s) && ! str_contains($s, '://')) {
            if (! self::isValidVkPathSegment($s)) {
                return null;
            }

            return 'https://vk.com/'.$s;
        }

        return null;
    }

    private static function isValidVkPathSegment(string $id): bool
    {
        if ($id === '') {
            return false;
        }

        if (strcasecmp($id, 'vk') === 0) {
            return false;
        }

        return (bool) preg_match('/^[a-zA-Z0-9._-]+$/', $id);
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
