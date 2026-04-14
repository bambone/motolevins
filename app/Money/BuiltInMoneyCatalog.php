<?php

namespace App\Money;

final class BuiltInMoneyCatalog
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function currencyRows(): array
    {
        return [
            ['code' => 'RUB', 'name' => 'Российский рубль', 'symbol' => "\u{20BD}", 'decimal_places' => 2, 'active' => true, 'default_locale' => 'ru_RU'],
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2, 'active' => true, 'default_locale' => 'en_US'],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'decimal_places' => 2, 'active' => true, 'default_locale' => 'de_DE'],
            ['code' => 'JPY', 'name' => 'Japanese Yen', 'symbol' => '¥', 'decimal_places' => 0, 'active' => true, 'default_locale' => 'ja_JP'],
        ];
    }

    /**
     * @return array<string, CurrencyDefinition>
     */
    public static function currenciesByCode(): array
    {
        $out = [];
        foreach (self::currencyRows() as $row) {
            $c = CurrencyDefinition::fromArray($row);
            $out[$c->code] = $c;
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultNewTenantDefaults(): array
    {
        return [
            'base_currency_code' => 'RUB',
            'fraction_display_mode' => 'auto',
            'display_scale_exponent' => 0,
            'multi_currency_enabled' => false,
        ];
    }
}
