<?php

declare(strict_types=1);

namespace App\Scheduling;

use DateTime;
use DateTimeZone;

/**
 * Подписи часовых поясов для поиска по названию и по смещению (в т.ч. ввод «2» → +02, -02, +02:30).
 */
final class SchedulingTimezoneOptions
{
    private static ?array $cache = null;

    /**
     * @return array<string, string> идентификатор IANA => подпись
     */
    public static function all(): array
    {
        return self::$cache ??= self::build();
    }

    /**
     * @return array<string, string>
     */
    private static function build(): array
    {
        $out = [];
        foreach (DateTimeZone::listIdentifiers() as $id) {
            try {
                $tz = new DateTimeZone($id);
                $dt = new DateTime('now', $tz);
                $offset = $dt->format('P');
            } catch (\Throwable) {
                continue;
            }
            $out[$id] = "{$id} · UTC{$offset}";
        }
        ksort($out);

        return $out;
    }
}
