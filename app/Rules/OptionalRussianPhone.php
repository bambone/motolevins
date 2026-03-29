<?php

namespace App\Rules;

use App\Support\RussianPhone;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class OptionalRussianPhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! RussianPhone::isValid((string) $value)) {
            $fail('Укажите номер в формате +7 (XXX) XXX-XX-XX (российский код и 10 цифр после +7).');
        }
    }
}
