<?php

namespace App\Money;

/**
 * Unified view/API payload for a formatted money value.
 */
final readonly class MoneyViewData
{
    public function __construct(
        public string $currencyCode,
        public string $currencySymbol,
        public int $tenantDisplayScaleExponent,
        public int $appliedDisplayScaleExponent,
        public string $displayUnitSuffix,
        public int $decimalPlaces,
        public string $logicalMajorAmount,
        public string $displayAmount,
        public string $formatted,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'currency_code' => $this->currencyCode,
            'currency_symbol' => $this->currencySymbol,
            'display_scale_exponent' => $this->tenantDisplayScaleExponent,
            'applied_display_scale_exponent' => $this->appliedDisplayScaleExponent,
            'display_unit_suffix' => $this->displayUnitSuffix,
            'decimal_places' => $this->decimalPlaces,
            'logical_major_amount' => $this->logicalMajorAmount,
            'display_amount' => $this->displayAmount,
            'formatted' => $this->formatted,
        ];
    }
}
