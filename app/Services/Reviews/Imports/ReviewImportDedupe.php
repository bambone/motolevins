<?php

declare(strict_types=1);

namespace App\Services\Reviews\Imports;

use DateTimeInterface;

final class ReviewImportDedupe
{
    public static function hashExternal(string $provider, string $normalizedSourceRef, string $externalId): string
    {
        return hash('sha256', $provider.'|'.$normalizedSourceRef.'|'.$externalId);
    }

    public static function hashNoExternal(string $provider, ?string $author, ?string $reviewedAtIso, string $body): string
    {
        $normBody = self::normalizeForHash($body);
        $a = $author !== null ? mb_strtolower(trim($author)) : '';
        $d = $reviewedAtIso ?? '';

        return hash('sha256', $provider.'|'.$a.'|'.$d.'|'.$normBody);
    }

    public static function normalizeForHash(string $body): string
    {
        $t = trim(preg_replace('/\s+/u', ' ', strip_tags($body)) ?? '');

        return mb_strtolower($t);
    }

    public static function reviewedAtIso(?DateTimeInterface $dt): ?string
    {
        if ($dt === null) {
            return null;
        }

        return $dt->format(DateTimeInterface::ATOM);
    }
}
