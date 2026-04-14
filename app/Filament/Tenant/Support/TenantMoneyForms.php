<?php

namespace App\Filament\Tenant\Support;

use App\Money\MoneyBindingRegistry;
use App\Money\MoneyDisplayLabels;
use App\Money\MoneyFormatter;
use App\Money\MoneyParser;
use App\Money\TenantMoneySettingsResolver;
use Filament\Forms\Components\TextInput;
use Illuminate\Validation\ValidationException;

final class TenantMoneyForms
{
    public static function moneyTextInput(
        string $name,
        string $bindingKey,
        ?string $label = null,
        bool $required = false,
        bool $nullableStorage = false,
    ): TextInput {
        $suffix = self::suffixForBinding($bindingKey);

        return TextInput::make($name)
            ->label($label)
            ->required($required)
            ->suffix($suffix)
            ->extraInputAttributes(['inputmode' => 'decimal'])
            ->formatStateUsing(function ($state) use ($bindingKey, $nullableStorage): ?string {
                $t = currentTenant();
                if ($t === null) {
                    return $state !== null ? (string) $state : null;
                }
                if ($nullableStorage && ($state === null || $state === '')) {
                    return '';
                }
                $storage = (int) $state;

                return app(MoneyFormatter::class)->formatStorageInt($storage, $bindingKey, $t)->displayAmount;
            })
            ->dehydrateStateUsing(function ($state) use ($bindingKey, $nullableStorage, $required) {
                $t = currentTenant();
                if ($t === null) {
                    return $state;
                }
                if ($nullableStorage && ($state === null || trim((string) $state) === '')) {
                    return null;
                }
                try {
                    return app(MoneyParser::class)->parseToStorageInt(
                        $state,
                        $bindingKey,
                        $t,
                        allowEmptyAsZero: ! $nullableStorage && ! $required,
                    );
                } catch (ValidationException $e) {
                    throw $e;
                }
            });
    }

    private static function suffixForBinding(string $bindingKey): string
    {
        $t = currentTenant();
        if ($t === null) {
            return '';
        }
        $resolver = app(TenantMoneySettingsResolver::class);
        $settings = $resolver->resolveForTenant($t);
        $binding = MoneyBindingRegistry::get($bindingKey);
        $currency = $resolver->resolveCurrencyForBinding($t, $binding);
        $scale = $binding->effectiveDisplayScaleExponent($settings->displayScaleExponent);

        return MoneyDisplayLabels::displayUnitSuffix($currency, $scale, $settings->displayUnitLabelOverride);
    }
}
