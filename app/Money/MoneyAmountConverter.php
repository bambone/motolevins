<?php

namespace App\Money;

use App\Money\Enums\MoneyStorageMode;

final class MoneyAmountConverter
{
    private const BC_SCALE = 12;

    public function storageIntToLogicalMajorString(int $storage, MoneyFieldBinding $binding, int $decimalPlaces): string
    {
        if ($binding->storageMode === MoneyStorageMode::MajorInteger) {
            return (string) $storage;
        }

        $divisor = bcpow('10', (string) max(0, $decimalPlaces), 0);

        return bcdiv((string) $storage, $divisor, max(0, $decimalPlaces));
    }

    public function logicalMajorStringToStorageInt(string $logicalMajor, MoneyFieldBinding $binding, int $decimalPlaces): int
    {
        $logicalMajor = trim($logicalMajor);
        if ($logicalMajor === '') {
            return 0;
        }

        if ($binding->storageMode === MoneyStorageMode::MajorInteger) {
            return (int) round((float) $logicalMajor);
        }

        $mult = bcpow('10', (string) max(0, $decimalPlaces), 0);
        $raw = bcmul($logicalMajor, $mult, 0);

        return (int) $raw;
    }

    public function logicalMajorToDisplayAmountString(string $logicalMajor, MoneyFieldBinding $binding, int $tenantDisplayScaleExponent): string
    {
        $scale = $binding->effectiveDisplayScaleExponent($tenantDisplayScaleExponent);
        if ($scale <= 0) {
            return $logicalMajor;
        }

        $div = bcpow('10', (string) $scale, 0);

        return bcdiv($logicalMajor, $div, self::BC_SCALE);
    }

    public function displayAmountToLogicalMajorString(string $displayAmount, MoneyFieldBinding $binding, int $tenantDisplayScaleExponent): string
    {
        $displayAmount = trim(str_replace(' ', '', $displayAmount));
        $displayAmount = str_replace(',', '.', $displayAmount);
        if ($displayAmount === '') {
            return '0';
        }

        $scale = $binding->effectiveDisplayScaleExponent($tenantDisplayScaleExponent);
        if ($scale <= 0) {
            return $displayAmount;
        }

        $mult = bcpow('10', (string) $scale, 0);

        return bcmul($displayAmount, $mult, self::BC_SCALE);
    }
}
