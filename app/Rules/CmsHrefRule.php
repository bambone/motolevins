<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Allows CMS-safe links: relative path, hash, http(s), mailto, tel.
 * Rejects javascript:, data:, vbscript:, protocol-relative //…, ASCII control characters,
 * and other colon-schemes not in the allowlist.
 */
final class CmsHrefRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $v = trim((string) $value);
        if ($v === '') {
            return;
        }

        $lower = strtolower($v);
        $dangerousPrefixes = ['javascript:', 'data:', 'vbscript:'];
        foreach ($dangerousPrefixes as $prefix) {
            if (str_starts_with($lower, $prefix)) {
                $fail(__('Недопустимая схема ссылки.'));

                return;
            }
        }

        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $v) === 1) {
            $fail(__('Недопустимая ссылка.'));

            return;
        }

        if (str_starts_with($v, '//')) {
            $fail(__('Разрешены только http(s), mailto:, tel:, путь с «/» или якорь «#».'));

            return;
        }

        if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $v) === 1) {
            if (str_starts_with($lower, 'http://') || str_starts_with($lower, 'https://')) {
                return;
            }
            if (str_starts_with($lower, 'mailto:') || str_starts_with($lower, 'tel:')) {
                return;
            }
            $fail(__('Разрешены только http(s), mailto:, tel:, путь с «/» или якорь «#».'));

            return;
        }

        if (str_starts_with($v, '/') || str_starts_with($v, '#')) {
            return;
        }

        $fail(__('Укажите ссылку: путь с «/», якорь «#…», https://…, mailto: или tel:.'));
    }
}
