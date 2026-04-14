<?php

namespace App\Money;

use App\Models\Tenant;
use Illuminate\Validation\ValidationException;

final class MoneyParser
{
    public function __construct(
        private readonly MoneyAmountConverter $converter,
        private readonly TenantMoneySettingsResolver $resolver,
    ) {}

    /**
     * @throws ValidationException
     */
    public function parseToStorageInt(
        mixed $raw,
        string $bindingKey,
        Tenant $tenant,
        bool $allowEmptyAsZero = true,
    ): int {
        $binding = MoneyBindingRegistry::get($bindingKey);
        $settings = $this->resolver->resolveForTenant($tenant);
        $currency = $this->resolver->resolveCurrencyForBinding($tenant, $binding);

        if ($raw === null || $raw === '') {
            if (! $allowEmptyAsZero) {
                throw ValidationException::withMessages(['money' => 'Укажите сумму.']);
            }

            return 0;
        }

        if (is_numeric($raw)) {
            $raw = (string) $raw;
        }
        if (! is_string($raw)) {
            throw ValidationException::withMessages(['money' => 'Некорректная сумма.']);
        }

        $display = trim(str_replace(["\u{00A0}", ' ', '_'], '', $raw), " \t\n\r\0\x0B");
        $display = str_replace(',', '.', $display);
        if ($display === '' || $display === '-') {
            throw ValidationException::withMessages(['money' => 'Некорректная сумма.']);
        }

        if (! is_numeric($display)) {
            throw ValidationException::withMessages(['money' => 'Некорректная сумма.']);
        }

        $logical = $this->converter->displayAmountToLogicalMajorString($display, $binding, $settings->displayScaleExponent);

        if (! $binding->allowNegative && bccomp($logical, '0', 12) < 0) {
            throw ValidationException::withMessages(['money' => 'Сумма не может быть отрицательной.']);
        }

        if ($binding->minLogicalMajor !== null && bccomp($logical, $binding->minLogicalMajor, 12) < 0) {
            throw ValidationException::withMessages(['money' => 'Сумма ниже допустимого минимума.']);
        }
        if ($binding->maxLogicalMajor !== null && bccomp($logical, $binding->maxLogicalMajor, 12) > 0) {
            throw ValidationException::withMessages(['money' => 'Сумма выше допустимого максимума.']);
        }

        $storage = $this->converter->logicalMajorStringToStorageInt($logical, $binding, $currency->decimalPlaces);

        return $storage;
    }

    /**
     * Like {@see parseToStorageInt} but returns null when input empty and $allowEmptyAsNull.
     */
    public function parseToStorageIntNullable(
        mixed $raw,
        string $bindingKey,
        Tenant $tenant,
    ): ?int {
        if ($raw === null || $raw === '') {
            return null;
        }

        return $this->parseToStorageInt($raw, $bindingKey, $tenant, allowEmptyAsZero: false);
    }
}
