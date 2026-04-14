<?php

namespace App\Money;

use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Money\Enums\MoneyCurrencySource;
use App\Money\Enums\MoneyFractionDisplayMode;

final class TenantMoneySettingsResolver
{
    public function __construct() {}

    /**
     * @return array<string, CurrencyDefinition>
     */
    public function platformCurrenciesByCode(): array
    {
        $builtIn = BuiltInMoneyCatalog::currenciesByCode();
        $stored = PlatformSetting::get('money.currencies', []);
        if (! is_array($stored) || $stored === []) {
            return $builtIn;
        }

        $merged = $builtIn;
        foreach ($stored as $row) {
            if (! is_array($row)) {
                continue;
            }
            $c = CurrencyDefinition::fromArray($row);
            if ($c->code !== '') {
                $merged[$c->code] = $c;
            }
        }

        return $merged;
    }

    /**
     * @return array<string, mixed>
     */
    public function platformDefaultsForNewTenants(): array
    {
        $defaults = PlatformSetting::get('money.defaults_for_new_tenants', []);
        if (! is_array($defaults) || $defaults === []) {
            return BuiltInMoneyCatalog::defaultNewTenantDefaults();
        }

        return array_merge(BuiltInMoneyCatalog::defaultNewTenantDefaults(), $defaults);
    }

    public function tenantMulticurrencyAllowed(): bool
    {
        return (bool) PlatformSetting::get('money.tenant_multicurrency_allowed', false);
    }

    public function resolveCurrency(string $code): CurrencyDefinition
    {
        $code = strtoupper(trim($code));
        $all = $this->platformCurrenciesByCode();

        return $all[$code] ?? new CurrencyDefinition(
            code: $code !== '' ? $code : 'XXX',
            name: $code,
            symbol: $code,
            decimalPlaces: 2,
            active: true,
        );
    }

    public function resolveForTenant(Tenant $tenant): TenantMoneySettings
    {
        $defaults = $this->platformDefaultsForNewTenants();
        $tid = (int) $tenant->id;

        $base = strtoupper(trim((string) TenantSetting::getForTenant($tid, 'money.base_currency_code', $defaults['base_currency_code'] ?? 'RUB')));
        if ($base === '') {
            $base = strtoupper(trim((string) ($tenant->currency ?? 'RUB')));
        }

        $fractionRaw = (string) TenantSetting::getForTenant($tid, 'money.fraction_display_mode', $defaults['fraction_display_mode'] ?? 'auto');
        $fraction = MoneyFractionDisplayMode::tryFrom($fractionRaw) ?? MoneyFractionDisplayMode::Auto;

        $scale = (int) TenantSetting::getForTenant($tid, 'money.display_scale_exponent', (int) ($defaults['display_scale_exponent'] ?? 0));
        if (! in_array($scale, [0, 3, 6], true)) {
            $scale = 0;
        }

        $override = TenantSetting::getForTenant($tid, 'money.display_unit_label_override', null);
        $override = is_string($override) ? $override : null;

        $multi = (bool) TenantSetting::getForTenant($tid, 'money.multi_currency_enabled', (bool) ($defaults['multi_currency_enabled'] ?? false));
        if ($multi && ! $this->tenantMulticurrencyAllowed()) {
            $multi = false;
        }

        $additional = TenantSetting::getForTenant($tid, 'money.additional_currency_codes', []);
        if (! is_array($additional)) {
            $additional = [];
        }
        $additional = array_values(array_filter(array_map(
            static fn ($c): string => strtoupper(trim((string) $c)),
            $additional
        )));

        return new TenantMoneySettings(
            tenantId: $tid,
            baseCurrencyCode: $base,
            fractionDisplayMode: $fraction,
            displayScaleExponent: $scale,
            displayUnitLabelOverride: $override,
            multiCurrencyEnabled: $multi,
            additionalCurrencyCodes: $additional,
        );
    }

    public function resolveCurrencyForBinding(Tenant $tenant, MoneyFieldBinding $binding): CurrencyDefinition
    {
        if ($binding->currencySource === MoneyCurrencySource::Fixed) {
            $code = strtoupper(trim((string) $binding->fixedCurrencyCode));

            return $this->resolveCurrency($code !== '' ? $code : 'XXX');
        }

        return $this->resolveCurrency($this->resolveForTenant($tenant)->baseCurrencyCode);
    }

    /**
     * Compact config for tenant public JS ({@see resources/js/tenant-money-format.js}).
     *
     * @return array<string, mixed>
     */
    public function publicJsonConfigForTenant(Tenant $tenant): array
    {
        $settings = $this->resolveForTenant($tenant);
        $currency = $this->resolveCurrency($settings->baseCurrencyCode);
        $appliedScale = in_array($settings->displayScaleExponent, [0, 3, 6], true)
            ? $settings->displayScaleExponent
            : 0;

        return [
            'currencyCode' => $currency->code,
            'currencySymbol' => $currency->symbol,
            'decimalPlaces' => $currency->decimalPlaces,
            'fractionDisplayMode' => $settings->fractionDisplayMode->value,
            'displayScaleExponent' => $settings->displayScaleExponent,
            'displayUnitSuffix' => MoneyDisplayLabels::displayUnitSuffix($currency, $appliedScale, $settings->displayUnitLabelOverride),
            'thousandsSeparator' => ' ',
            'decimalSeparator' => ',',
            'bindings' => MoneyBindingRegistry::jsBindingMetadata(),
        ];
    }
}
