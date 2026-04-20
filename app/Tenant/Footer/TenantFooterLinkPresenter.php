<?php

declare(strict_types=1);

namespace App\Tenant\Footer;

final class TenantFooterLinkPresenter
{
    /**
     * @param  string  $storedUrl  Для {@see TenantFooterLinkKind::Internal} — относительный путь (`/motorcycles`) или полный URL; путь предпочтителен в сидерах/CLI (без привязки к host).
     */
    public static function href(string $storedUrl, TenantFooterLinkKind $kind): string
    {
        $storedUrl = trim($storedUrl);

        return match ($kind) {
            TenantFooterLinkKind::Phone => self::telHref($storedUrl),
            TenantFooterLinkKind::Email => self::mailtoHref($storedUrl),
            TenantFooterLinkKind::Telegram => self::telegramHref($storedUrl),
            TenantFooterLinkKind::Whatsapp => self::whatsappHref($storedUrl),
            default => $storedUrl,
        };
    }

    public static function defaultTarget(TenantFooterLinkKind $kind, ?string $storedTarget): ?string
    {
        if ($storedTarget === '_self' || $storedTarget === '_blank') {
            return $storedTarget;
        }

        return match ($kind) {
            TenantFooterLinkKind::External, TenantFooterLinkKind::Document => '_blank',
            default => '_self',
        };
    }

    private static function telHref(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        return $digits !== '' ? 'tel:'.$digits : $raw;
    }

    private static function mailtoHref(string $raw): string
    {
        $t = trim($raw);

        return str_starts_with(strtolower($t), 'mailto:') ? $t : 'mailto:'.$t;
    }

    private static function telegramHref(string $raw): string
    {
        $h = preg_replace('#^https?://(www\.)?t\.me/#i', '', trim($raw));
        $h = ltrim((string) $h, '@');

        return 'https://t.me/'.$h;
    }

    private static function whatsappHref(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        return $digits !== '' ? 'https://wa.me/'.$digits : $raw;
    }
}
