<?php

namespace App\Rules;

use App\Http\Requests\StoreLeadRequest;
use App\Support\Phone\IntlPhoneNormalizer;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Телефон в международном формате — те же правила, что {@see StoreLeadRequest} и {@see IntlPhoneNormalizer}.
 */
final class ValidIntlPhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('Укажите корректный телефон в международном формате (например +7 для России).');

            return;
        }

        $normalized = IntlPhoneNormalizer::normalizePhone($value);
        if (! IntlPhoneNormalizer::validatePhone($normalized)) {
            $fail('Укажите корректный телефон в международном формате (например +7 для России).');
        }
    }
}
