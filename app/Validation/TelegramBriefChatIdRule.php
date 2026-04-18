<?php

declare(strict_types=1);

namespace App\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Для анкеты «Запись и уведомления»: пусто или числовой chat_id / @username канала.
 */
final class TelegramBriefChatIdRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $v = trim((string) $value);
        if ($v === '') {
            return;
        }
        if (preg_match('/^-?\d+$/', $v) && strlen($v) <= 32) {
            return;
        }
        if (preg_match('/^@[a-zA-Z][a-zA-Z0-9_]{4,31}$/', $v)) {
            return;
        }
        $fail('Укажите числовой chat_id (например 123456789 или −1001234567890) или @username публичного канала (латиница, цифры и _, 5–32 символа после @).');
    }
}
