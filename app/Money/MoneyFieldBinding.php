<?php

namespace App\Money;

use App\Money\Enums\MoneyCurrencySource;
use App\Money\Enums\MoneySemanticType;
use App\Money\Enums\MoneyStorageMode;

/**
 * Full contract for a persisted money field (storage ↔ logical ↔ display).
 */
final readonly class MoneyFieldBinding
{
    public function __construct(
        public string $key,
        public string $entityKey,
        public string $attribute,
        public MoneyStorageMode $storageMode,
        public MoneyCurrencySource $currencySource,
        public ?string $fixedCurrencyCode,
        public bool $supportsDisplayScale,
        public bool $supportsFractionDisplay,
        public ?string $minLogicalMajor,
        public ?string $maxLogicalMajor,
        public bool $allowNegative,
        public MoneySemanticType $semanticType,
        public ?string $jsBindingKey,
    ) {}

    public function effectiveDisplayScaleExponent(int $tenantDisplayScaleExponent): int
    {
        if (! $this->supportsDisplayScale) {
            return 0;
        }

        return in_array($tenantDisplayScaleExponent, [0, 3, 6], true) ? $tenantDisplayScaleExponent : 0;
    }
}
