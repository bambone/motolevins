<?php

namespace App\Money;

use App\Models\Tenant;
use App\Money\Enums\MoneyFractionDisplayMode;

final class MoneyFormatter
{
    public function __construct(
        private readonly MoneyAmountConverter $converter,
        private readonly TenantMoneySettingsResolver $resolver,
    ) {}

    public function formatStorageInt(
        int $storage,
        string $bindingKey,
        Tenant $tenant,
    ): MoneyViewData {
        $binding = MoneyBindingRegistry::get($bindingKey);
        $settings = $this->resolver->resolveForTenant($tenant);
        $currency = $this->resolver->resolveCurrencyForBinding($tenant, $binding);

        $logical = $this->converter->storageIntToLogicalMajorString($storage, $binding, $currency->decimalPlaces);
        $appliedScale = $binding->effectiveDisplayScaleExponent($settings->displayScaleExponent);
        $displayAmount = $this->converter->logicalMajorToDisplayAmountString($logical, $binding, $settings->displayScaleExponent);

        $fractionMode = $binding->supportsFractionDisplay
            ? $settings->effectiveFractionMode($currency)
            : MoneyFractionDisplayMode::Never;

        $suffix = MoneyDisplayLabels::displayUnitSuffix($currency, $appliedScale, $settings->displayUnitLabelOverride);

        $displayAmount = $this->normalizeBcNumber($displayAmount);
        $formattedNumber = $this->formatNumericString($displayAmount, $currency->decimalPlaces, $fractionMode);
        $formatted = trim($formattedNumber.' '.$suffix);

        return new MoneyViewData(
            currencyCode: $currency->code,
            currencySymbol: $currency->symbol,
            tenantDisplayScaleExponent: $settings->displayScaleExponent,
            appliedDisplayScaleExponent: $appliedScale,
            displayUnitSuffix: $suffix,
            decimalPlaces: $currency->decimalPlaces,
            logicalMajorAmount: $logical,
            displayAmount: $displayAmount,
            formatted: $formatted,
        );
    }

    public function formatStorageIntNullable(
        ?int $storage,
        string $bindingKey,
        Tenant $tenant,
    ): ?MoneyViewData {
        if ($storage === null) {
            return null;
        }

        return $this->formatStorageInt($storage, $bindingKey, $tenant);
    }

    private function normalizeBcNumber(string $amount): string
    {
        $amount = str_replace(',', '.', trim($amount));
        if ($amount === '') {
            return '0';
        }
        if (! str_contains($amount, '.')) {
            return $amount;
        }
        $amount = rtrim(rtrim($amount, '0'), '.');

        return $amount === '' || $amount === '-' ? '0' : $amount;
    }

    private function formatNumericString(string $amount, int $decimalPlaces, MoneyFractionDisplayMode $mode): string
    {
        $normalized = str_replace(',', '.', trim($amount));
        if ($normalized === '') {
            $normalized = '0';
        }

        if ($mode === MoneyFractionDisplayMode::Never || $decimalPlaces === 0) {
            $rounded = (string) (int) round((float) $normalized);

            return $this->formatThousands($rounded);
        }

        $parts = explode('.', $normalized, 2);
        $intPart = $parts[0] !== '' ? $parts[0] : '0';
        $frac = $parts[1] ?? '';

        if ($mode === MoneyFractionDisplayMode::Always) {
            $frac = str_pad(substr($frac.'0000000000', 0, $decimalPlaces), $decimalPlaces, '0');

            return $this->formatThousands($intPart).','.$frac;
        }

        // auto: trim insignificant zeros in fractional part
        $frac = substr($frac.'0000000000', 0, $decimalPlaces);
        $frac = rtrim($frac, '0');
        if ($frac === '') {
            return $this->formatThousands($intPart);
        }

        return $this->formatThousands($intPart).','.$frac;
    }

    private function formatThousands(string $intPart): string
    {
        $sign = '';
        if (str_starts_with($intPart, '-')) {
            $sign = '-';
            $intPart = substr($intPart, 1);
        }
        $intPart = ltrim($intPart, '0') ?: '0';
        $reversed = strrev($intPart);
        $chunks = str_split($reversed, 3);
        $joined = implode(' ', $chunks);

        return $sign.strrev($joined);
    }
}
