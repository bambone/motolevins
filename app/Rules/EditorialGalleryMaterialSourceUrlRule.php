<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Ссылка на внешний материал: только http(s), без относительных путей, mailto и tel.
 */
final class EditorialGalleryMaterialSourceUrlRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $v = trim((string) $value);
        if ($v === '') {
            return;
        }

        $lower = strtolower($v);
        foreach (['javascript:', 'data:', 'vbscript:'] as $prefix) {
            if (str_starts_with($lower, $prefix)) {
                $fail(__('Недопустимая схема ссылки.'));

                return;
            }
        }

        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $v) === 1) {
            $fail(__('Недопустимая ссылка.'));

            return;
        }

        // Protocol-relative URLs are not allowed (browser would resolve to current host).
        if (str_starts_with($v, '//')) {
            $fail(__('Ссылка на материал: укажите полный адрес с https:// или http://.'));

            return;
        }

        if (! str_starts_with($lower, 'http://') && ! str_starts_with($lower, 'https://')) {
            $fail(__('Ссылка на материал: укажите полный адрес с https:// или http://.'));

            return;
        }

        $parsed = parse_url($v);
        if ($parsed === false) {
            $fail(__('Ссылка на материал: укажите корректный адрес с именем хоста.'));

            return;
        }

        $host = $parsed['host'] ?? null;
        if (! is_string($host) || trim($host) === '') {
            $fail(__('Ссылка на материал: укажите корректный адрес с именем хоста.'));

            return;
        }
    }
}
