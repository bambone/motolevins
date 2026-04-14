<?php

namespace App\Filament\Platform\Pages;

use App\Filament\Platform\Pages\Concerns\GrantsPlatformPageAccess;
use App\Models\PlatformSetting;
use App\Money\BuiltInMoneyCatalog;
use App\Money\Enums\MoneyFractionDisplayMode;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use JsonException;
use UnitEnum;

class PlatformMoneySettingsPage extends Page
{
    use GrantsPlatformPageAccess;

    protected static ?string $navigationLabel = 'Деньги и валюты';

    protected static ?string $title = 'Деньги и валюты (платформа)';

    protected static ?string $slug = 'money-settings';

    protected static string|UnitEnum|null $navigationGroup = 'Платформа';

    protected static ?int $navigationSort = 13;

    protected static ?string $panel = 'platform';

    protected string $view = 'filament.pages.platform.money-settings';

    public ?array $data = [];

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $currencies = PlatformSetting::get('money.currencies', []);
        if (! is_array($currencies) || $currencies === []) {
            $currencies = BuiltInMoneyCatalog::currencyRows();
        }

        $defaults = PlatformSetting::get('money.defaults_for_new_tenants', []);
        if (! is_array($defaults) || $defaults === []) {
            $defaults = BuiltInMoneyCatalog::defaultNewTenantDefaults();
        }

        $this->getSchema('form')->fill([
            'money_currencies_json' => json_encode($currencies, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'money_default_base_currency' => (string) ($defaults['base_currency_code'] ?? 'RUB'),
            'money_default_fraction' => (string) ($defaults['fraction_display_mode'] ?? 'auto'),
            'money_default_scale' => (int) ($defaults['display_scale_exponent'] ?? 0),
            'money_default_multi' => (bool) ($defaults['multi_currency_enabled'] ?? false),
            'money_tenant_multicurrency_allowed' => (bool) PlatformSetting::get('money.tenant_multicurrency_allowed', false),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Справочник валют')
                    ->description('JSON-массив объектов: code, name, symbol, decimal_places, active, default_locale. Пустой JSON — сброс к встроенному каталогу при сохранении недопустим; оставьте валидный массив.')
                    ->schema([
                        Textarea::make('money_currencies_json')
                            ->label('money.currencies')
                            ->rows(16)
                            ->required(),
                    ]),
                Section::make('Дефолты для новых клиентов')
                    ->schema([
                        Select::make('money_default_base_currency')
                            ->label('Базовая валюта')
                            ->options(collect(BuiltInMoneyCatalog::currencyRows())->mapWithKeys(
                                fn (array $r): array => [strtoupper((string) $r['code']) => strtoupper((string) $r['code']).' — '.((string) $r['name'])]
                            ))
                            ->required()
                            ->native(true),
                        Select::make('money_default_fraction')
                            ->label('Режим дробной части')
                            ->options([
                                MoneyFractionDisplayMode::Auto->value => 'auto — только значимая дробь',
                                MoneyFractionDisplayMode::Always->value => 'always — всегда полные знаки',
                                MoneyFractionDisplayMode::Never->value => 'never — без дробной части в отображении',
                            ])
                            ->required()
                            ->native(true),
                        Select::make('money_default_scale')
                            ->label('Масштаб UI (разрядность ввода/показа)')
                            ->options([
                                0 => 'Единицы валюты',
                                3 => 'Тысячи',
                                6 => 'Миллионы',
                            ])
                            ->required()
                            ->native(true),
                        Toggle::make('money_default_multi')
                            ->label('Мультивалютность включена по умолчанию')
                            ->helperText('Только если глобально разрешено ниже.'),
                    ])->columns(2),
                Section::make('Политика')
                    ->schema([
                        Toggle::make('money_tenant_multicurrency_allowed')
                            ->label('Разрешить клиентам опциональную мультивалютность')
                            ->helperText('Без автокурсов; доп. цены — отдельная задача.'),
                    ]),
            ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $state = $this->getSchema('form')->getState();

        $raw = trim((string) ($state['money_currencies_json'] ?? ''));
        try {
            $currencies = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            Notification::make()->title('Некорректный JSON валют')->danger()->send();

            return;
        }
        if (! is_array($currencies)) {
            Notification::make()->title('Валюты должны быть JSON-массивом')->danger()->send();

            return;
        }
        PlatformSetting::set('money.currencies', $currencies, 'json');

        $defaults = [
            'base_currency_code' => strtoupper(trim((string) ($state['money_default_base_currency'] ?? 'RUB'))),
            'fraction_display_mode' => (string) ($state['money_default_fraction'] ?? 'auto'),
            'display_scale_exponent' => (int) ($state['money_default_scale'] ?? 0),
            'multi_currency_enabled' => (bool) ($state['money_default_multi'] ?? false),
        ];
        if (! in_array($defaults['display_scale_exponent'], [0, 3, 6], true)) {
            $defaults['display_scale_exponent'] = 0;
        }
        if (MoneyFractionDisplayMode::tryFrom($defaults['fraction_display_mode']) === null) {
            $defaults['fraction_display_mode'] = MoneyFractionDisplayMode::Auto->value;
        }
        PlatformSetting::set('money.defaults_for_new_tenants', $defaults, 'json');

        PlatformSetting::set(
            'money.tenant_multicurrency_allowed',
            (bool) ($state['money_tenant_multicurrency_allowed'] ?? false),
            'boolean'
        );

        Notification::make()->title('Сохранено')->success()->send();
    }
}
