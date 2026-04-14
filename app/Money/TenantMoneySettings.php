<?php

namespace App\Money;

use App\Money\Enums\MoneyFractionDisplayMode;

final readonly class TenantMoneySettings
{
    /**
     * @param  list<string>  $additionalCurrencyCodes
     */
    public function __construct(
        public int $tenantId,
        public string $baseCurrencyCode,
        public MoneyFractionDisplayMode $fractionDisplayMode,
        public int $displayScaleExponent,
        public ?string $displayUnitLabelOverride,
        public bool $multiCurrencyEnabled,
        public array $additionalCurrencyCodes,
    ) {}

    public function effectiveFractionMode(CurrencyDefinition $currency): MoneyFractionDisplayMode
    {
        if ($currency->decimalPlaces === 0) {
            return MoneyFractionDisplayMode::Never;
        }

        return $this->fractionDisplayMode;
    }
}
