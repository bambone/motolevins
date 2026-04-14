<?php

namespace App\Money;

use App\Money\Enums\MoneyCurrencySource;
use App\Money\Enums\MoneySemanticType;
use App\Money\Enums\MoneyStorageMode;
use App\Money\Exceptions\UnknownMoneyBindingException;
use Illuminate\Support\Facades\Log;

final class MoneyBindingRegistry
{
    public const MOTORCYCLE_PRICE_PER_DAY = 'motorcycle.price_per_day';

    public const MOTORCYCLE_PRICE_2_3_DAYS = 'motorcycle.price_2_3_days';

    public const MOTORCYCLE_PRICE_WEEK = 'motorcycle.price_week';

    public const BOOKING_TOTAL_PRICE = 'booking.total_price';

    public const BOOKING_PRICE_PER_DAY_SNAPSHOT = 'booking.price_per_day_snapshot';

    public const BOOKING_DEPOSIT_AMOUNT = 'booking.deposit_amount';

    public const TENANT_SERVICE_PROGRAM_PRICE_AMOUNT = 'tenant_service_program.price_amount';

    public const ADDON_PRICE = 'addon.price';

    public const BOOKING_ADDON_PRICE_SNAPSHOT = 'booking_addon.price_snapshot';

    public const BIKE_PRICE_PER_DAY = 'bike.price_per_day';

    /**
     * @return array<string, MoneyFieldBinding>
     */
    public static function definitions(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $priceScaled = fn (string $key, string $entity, string $attr, ?string $jsKey = null): MoneyFieldBinding => new MoneyFieldBinding(
            key: $key,
            entityKey: $entity,
            attribute: $attr,
            storageMode: MoneyStorageMode::MajorInteger,
            currencySource: MoneyCurrencySource::TenantBase,
            fixedCurrencyCode: null,
            supportsDisplayScale: true,
            supportsFractionDisplay: true,
            minLogicalMajor: '0',
            maxLogicalMajor: null,
            allowNegative: false,
            semanticType: MoneySemanticType::Price,
            jsBindingKey: $jsKey ?? $key,
        );

        $cache = [
            self::MOTORCYCLE_PRICE_PER_DAY => $priceScaled(self::MOTORCYCLE_PRICE_PER_DAY, 'motorcycle', 'price_per_day'),
            self::MOTORCYCLE_PRICE_2_3_DAYS => new MoneyFieldBinding(
                key: self::MOTORCYCLE_PRICE_2_3_DAYS,
                entityKey: 'motorcycle',
                attribute: 'price_2_3_days',
                storageMode: MoneyStorageMode::MajorInteger,
                currencySource: MoneyCurrencySource::TenantBase,
                fixedCurrencyCode: null,
                supportsDisplayScale: true,
                supportsFractionDisplay: true,
                minLogicalMajor: null,
                maxLogicalMajor: null,
                allowNegative: false,
                semanticType: MoneySemanticType::Price,
                jsBindingKey: self::MOTORCYCLE_PRICE_2_3_DAYS,
            ),
            self::MOTORCYCLE_PRICE_WEEK => new MoneyFieldBinding(
                key: self::MOTORCYCLE_PRICE_WEEK,
                entityKey: 'motorcycle',
                attribute: 'price_week',
                storageMode: MoneyStorageMode::MajorInteger,
                currencySource: MoneyCurrencySource::TenantBase,
                fixedCurrencyCode: null,
                supportsDisplayScale: true,
                supportsFractionDisplay: true,
                minLogicalMajor: null,
                maxLogicalMajor: null,
                allowNegative: false,
                semanticType: MoneySemanticType::Price,
                jsBindingKey: self::MOTORCYCLE_PRICE_WEEK,
            ),
            self::BOOKING_TOTAL_PRICE => new MoneyFieldBinding(
                key: self::BOOKING_TOTAL_PRICE,
                entityKey: 'booking',
                attribute: 'total_price',
                storageMode: MoneyStorageMode::MajorInteger,
                currencySource: MoneyCurrencySource::TenantBase,
                fixedCurrencyCode: null,
                supportsDisplayScale: false,
                supportsFractionDisplay: true,
                minLogicalMajor: '0',
                maxLogicalMajor: null,
                allowNegative: false,
                semanticType: MoneySemanticType::Price,
                jsBindingKey: self::BOOKING_TOTAL_PRICE,
            ),
            self::BOOKING_PRICE_PER_DAY_SNAPSHOT => new MoneyFieldBinding(
                key: self::BOOKING_PRICE_PER_DAY_SNAPSHOT,
                entityKey: 'booking',
                attribute: 'price_per_day_snapshot',
                storageMode: MoneyStorageMode::MajorInteger,
                currencySource: MoneyCurrencySource::TenantBase,
                fixedCurrencyCode: null,
                supportsDisplayScale: false,
                supportsFractionDisplay: true,
                minLogicalMajor: '0',
                maxLogicalMajor: null,
                allowNegative: false,
                semanticType: MoneySemanticType::Price,
                jsBindingKey: self::BOOKING_PRICE_PER_DAY_SNAPSHOT,
            ),
            self::BOOKING_DEPOSIT_AMOUNT => new MoneyFieldBinding(
                key: self::BOOKING_DEPOSIT_AMOUNT,
                entityKey: 'booking',
                attribute: 'deposit_amount',
                storageMode: MoneyStorageMode::MajorInteger,
                currencySource: MoneyCurrencySource::TenantBase,
                fixedCurrencyCode: null,
                supportsDisplayScale: false,
                supportsFractionDisplay: true,
                minLogicalMajor: '0',
                maxLogicalMajor: null,
                allowNegative: false,
                semanticType: MoneySemanticType::Deposit,
                jsBindingKey: self::BOOKING_DEPOSIT_AMOUNT,
            ),
            self::TENANT_SERVICE_PROGRAM_PRICE_AMOUNT => new MoneyFieldBinding(
                key: self::TENANT_SERVICE_PROGRAM_PRICE_AMOUNT,
                entityKey: 'tenant_service_program',
                attribute: 'price_amount',
                storageMode: MoneyStorageMode::MinorInteger,
                currencySource: MoneyCurrencySource::TenantBase,
                fixedCurrencyCode: null,
                supportsDisplayScale: true,
                supportsFractionDisplay: true,
                minLogicalMajor: null,
                maxLogicalMajor: null,
                allowNegative: false,
                semanticType: MoneySemanticType::Price,
                jsBindingKey: self::TENANT_SERVICE_PROGRAM_PRICE_AMOUNT,
            ),
            self::ADDON_PRICE => $priceScaled(self::ADDON_PRICE, 'addon', 'price'),
            self::BOOKING_ADDON_PRICE_SNAPSHOT => new MoneyFieldBinding(
                key: self::BOOKING_ADDON_PRICE_SNAPSHOT,
                entityKey: 'booking_addon',
                attribute: 'price_snapshot',
                storageMode: MoneyStorageMode::MajorInteger,
                currencySource: MoneyCurrencySource::TenantBase,
                fixedCurrencyCode: null,
                supportsDisplayScale: false,
                supportsFractionDisplay: true,
                minLogicalMajor: '0',
                maxLogicalMajor: null,
                allowNegative: false,
                semanticType: MoneySemanticType::Price,
                jsBindingKey: self::BOOKING_ADDON_PRICE_SNAPSHOT,
            ),
            self::BIKE_PRICE_PER_DAY => $priceScaled(self::BIKE_PRICE_PER_DAY, 'bike', 'price_per_day'),
        ];

        return $cache;
    }

    public static function get(string $key): MoneyFieldBinding
    {
        $all = self::definitions();
        if (isset($all[$key])) {
            return $all[$key];
        }

        Log::warning('money.unknown_binding', ['key' => $key]);
        if (config('money.strict_bindings')) {
            throw UnknownMoneyBindingException::forKey($key);
        }

        return self::fallbackBinding($key);
    }

    public static function tryGet(string $key): ?MoneyFieldBinding
    {
        return self::definitions()[$key] ?? null;
    }

    /**
     * Metadata for public JS ({@see TenantMoneySettingsResolver::publicJsonConfigForTenant()}).
     *
     * @return array<string, array{applyScale: bool, storageMode: string}>
     */
    public static function jsBindingMetadata(): array
    {
        $out = [];
        foreach (self::definitions() as $binding) {
            $jk = $binding->jsBindingKey ?? $binding->key;
            $out[$jk] = [
                'applyScale' => $binding->supportsDisplayScale,
                'storageMode' => $binding->storageMode->value,
            ];
        }

        return $out;
    }

    private static function fallbackBinding(string $key): MoneyFieldBinding
    {
        return new MoneyFieldBinding(
            key: $key,
            entityKey: 'unknown',
            attribute: 'unknown',
            storageMode: MoneyStorageMode::MajorInteger,
            currencySource: MoneyCurrencySource::TenantBase,
            fixedCurrencyCode: null,
            supportsDisplayScale: false,
            supportsFractionDisplay: true,
            minLogicalMajor: null,
            maxLogicalMajor: null,
            allowNegative: false,
            semanticType: MoneySemanticType::Other,
            jsBindingKey: $key,
        );
    }
}
