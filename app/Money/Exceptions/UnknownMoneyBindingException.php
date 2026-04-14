<?php

namespace App\Money\Exceptions;

use InvalidArgumentException;

final class UnknownMoneyBindingException extends InvalidArgumentException
{
    public static function forKey(string $key): self
    {
        return new self("Unknown money field binding: {$key}");
    }
}
