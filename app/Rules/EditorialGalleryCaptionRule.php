<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Plain-text подпись без HTML и типичного мусора из копипаста.
 */
final class EditorialGalleryCaptionRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $v = (string) $value;
        if ($v === '') {
            return;
        }
        if (preg_match('/[<>]/', $v) === 1) {
            $fail(__('Не используйте HTML в подписи.'));

            return;
        }
        if (str_contains($v, '&quot;') || str_contains($v, '&#')) {
            $fail(__('Вставьте текст без HTML-сущностей (кавычки — обычными символами).'));

            return;
        }
    }
}
