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
    /** Дефолт для форм и нормализации неизвестного значения (как у ресурсов расписания). */
    public const DEFAULT_IDENTIFIER = 'Europe/Moscow';

    private static ?array $cache = null;

    /**
     * Приводит строку к известному идентификатору из {@see self::all()} (регистр игнорируется).
     * Пустое или нераспознанное значение → {@see DEFAULT_IDENTIFIER}.
     */
    public static function normalizeToKnown(?string $candidate): string
    {
        $all = self::all();
        $trim = trim((string) $candidate);
        if ($trim !== '' && isset($all[$trim])) {
            return $trim;
        }
        if ($trim !== '') {
            foreach ($all as $id => $_) {
                if (strcasecmp($id, $trim) === 0) {
                    return $id;
                }
            }
        }

        return self::DEFAULT_IDENTIFIER;
    }

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
