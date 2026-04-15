<?php

namespace App\Tenant\Expert;

/**
 * Строит безопасный URL для iframe из share/page URL. В БД хранится исходная ссылка, не финальный src iframe.
 */
final class VideoEmbedUrlNormalizer
{
    /** @return non-empty-string|null */
    public static function toIframeSrc(string $provider, string $shareUrl): ?string
    {
        $provider = strtolower(trim($provider));
        $shareUrl = trim($shareUrl);
        if ($shareUrl === '' || $provider === '') {
            return null;
        }

        return match ($provider) {
            'youtube' => self::youtube($shareUrl),
            'vk' => self::vk($shareUrl),
            default => null,
        };
    }

    /** @return non-empty-string|null */
    private static function youtube(string $url): ?string
    {
        if (stripos($url, '<') !== false) {
            return null;
        }
        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            return null;
        }
        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['host'])) {
            return null;
        }
        $host = strtolower($parts['host']);
        if (! self::youtubeHostAllowed($host)) {
            return null;
        }

        $pathTrim = trim((string) ($parts['path'] ?? ''), '/');
        $id = null;

        if ($host === 'youtu.be') {
            $segments = $pathTrim === '' ? [] : explode('/', $pathTrim);
            if (isset($segments[0]) && $segments[0] === 'shorts' && isset($segments[1]) && $segments[1] !== '') {
                $id = $segments[1];
            } elseif (isset($segments[0]) && $segments[0] !== '') {
                $id = $segments[0];
            }
        }

        if ($id === null && in_array($host, ['www.youtube.com', 'youtube.com', 'm.youtube.com'], true)) {
            if (! empty($parts['query'])) {
                parse_str((string) $parts['query'], $q);
                if (! empty($q['v']) && is_string($q['v'])) {
                    $id = $q['v'];
                }
            }
            if ($id === null && $pathTrim !== '') {
                if (preg_match('#^(?:shorts|embed|live)/([a-zA-Z0-9_-]{6,64})(?:/|$)#', $pathTrim, $m) === 1) {
                    $id = $m[1];
                }
            }
        }

        if ($id === null || $id === '' || ! preg_match('/^[a-zA-Z0-9_-]{6,64}$/', $id)) {
            return null;
        }

        return 'https://www.youtube-nocookie.com/embed/'.rawurlencode($id).'?rel=0';
    }

    private static function youtubeHostAllowed(string $host): bool
    {
        return in_array($host, ['youtu.be', 'www.youtube.com', 'youtube.com', 'm.youtube.com'], true);
    }

    /** @return non-empty-string|null */
    private static function vk(string $url): ?string
    {
        if (stripos($url, '<') !== false) {
            return null;
        }
        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            return null;
        }
        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['host'])) {
            return null;
        }
        $host = strtolower((string) $parts['host']);
        if (! self::vkHostAllowed($host)) {
            return null;
        }

        $path = (string) ($parts['path'] ?? '');
        $query = $parts['query'] ?? null;

        if ($path !== '' && strcasecmp(basename($path), 'video_ext.php') === 0 && is_string($query) && $query !== '') {
            parse_str($query, $q);
            $oid = $q['oid'] ?? null;
            $id = $q['id'] ?? null;
            if ($oid === null || $id === null || $oid === '' || $id === '') {
                return null;
            }

            return 'https://vk.com/video_ext.php?oid='.rawurlencode((string) $oid).'&id='.rawurlencode((string) $id).'&hd=2';
        }

        if ($path !== '' && preg_match('#^/video(-?\d+)_(\d+)(?:/|\?|$)#', $path, $m) === 1) {
            $owner = $m[1];
            $vid = $m[2];

            return 'https://vk.com/video_ext.php?oid='.rawurlencode($owner).'&id='.rawurlencode($vid).'&hd=2';
        }

        return null;
    }

    private static function vkHostAllowed(string $host): bool
    {
        return in_array($host, ['vk.com', 'www.vk.com', 'm.vk.com'], true);
    }
}
