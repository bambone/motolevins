<?php

namespace App\Money;

final class MoneyDisplayLabels
{
    public static function displayUnitSuffix(
        CurrencyDefinition $currency,
        int $appliedDisplayScaleExponent,
        ?string $tenantOverride,
    ): string {
        if ($tenantOverride !== null && trim($tenantOverride) !== '' && $appliedDisplayScaleExponent === 0) {
            return trim($tenantOverride);
        }

        $sym = $currency->symbol;

        return match ($appliedDisplayScaleExponent) {
            6 => 'млн '.$sym,
            3 => 'тыс. '.$sym,
            default => $sym,
        };
    }
}
