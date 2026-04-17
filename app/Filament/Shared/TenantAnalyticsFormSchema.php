<?php

namespace App\Filament\Shared;

use App\Support\Analytics\AnalyticsSettingsFormMapper;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\View;

/**
 * Shared «Аналитика» fields: кабинет клиента, карточка клиента в платформе, маркетинг платформы (PlatformMarketingSettingsPage).
 * Form keys must stay in sync with {@see AnalyticsSettingsFormMapper}.
 */
final class TenantAnalyticsFormSchema
{
    /**
     * @return list<string>
     */
    public static function formFieldKeys(): array
    {
        return [
            'analytics_yandex_metrica_enabled',
            'analytics_yandex_counter_id',
            'analytics_yandex_webvisor_enabled',
            'analytics_yandex_clickmap_enabled',
            'analytics_yandex_track_links_enabled',
            'analytics_yandex_accurate_bounce_enabled',
            'analytics_ga4_enabled',
            'analytics_ga4_measurement_id',
        ];
    }

    /**
     * @param  Closure|bool  $visible  If false, section hidden (platform support must not see counter IDs).
     */
    public static function section(Closure|bool $visible = true): Section
    {
        $visibleClosure = $visible instanceof Closure
            ? $visible
            : fn (): bool => (bool) $visible;

        return Section::make('Аналитика')
            ->description('Подключение счётчиков только по ID. Не вставляйте код целиком — только идентификаторы. У переключателей Метрики и GA4 есть иконка «i» со справкой.')
            ->extraAttributes(['data-setup-target' => 'settings.analytics_yandex_ga'])
            ->visible($visibleClosure)
            ->headerActions([
                Action::make('analytics_help_yandex')
                    ->label('Как подключить Метрику')
                    ->link()
                    ->modalHeading('Яндекс Метрика: ID и настройки')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Закрыть')
                    ->modalWidth(Width::TwoExtraLarge)
                    ->modalContent(fn () => View::make('filament.shared.analytics-help-yandex')),
                Action::make('analytics_help_ga4')
                    ->label('Как подключить GA4')
                    ->link()
                    ->modalHeading('Google Analytics 4: Measurement ID')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Закрыть')
                    ->modalWidth(Width::TwoExtraLarge)
                    ->modalContent(fn () => View::make('filament.shared.analytics-help-ga4')),
            ])
            ->schema([
                Toggle::make('analytics_yandex_metrica_enabled')
                    ->label('Яндекс Метрика')
                    ->helperText('Включить официальный счётчик платформы (tag.js).')
                    ->hintAction(self::analyticsHintModal(
                        'hint_analytics_yandex_main',
                        'Яндекс Метрика',
                        'filament.shared.analytics-hints.yandex-main',
                    )),
                TextInput::make('analytics_yandex_counter_id')
                    ->label('ID счётчика Метрики')
                    ->helperText('Укажите только ID счётчика, например 12345678. Не вставляйте код Метрики целиком.')
                    ->maxLength(32),
                Toggle::make('analytics_yandex_webvisor_enabled')
                    ->label('Вебвизор')
                    ->default(false)
                    ->helperText('Запись сессий в Метрике для разбора поведения.')
                    ->hintAction(self::analyticsHintModal(
                        'hint_analytics_yandex_webvisor',
                        'Вебвизор',
                        'filament.shared.analytics-hints.yandex-webvisor',
                    )),
                Toggle::make('analytics_yandex_clickmap_enabled')
                    ->label('Карта кликов')
                    ->default(false)
                    ->helperText('Тепловая карта кликов в отчётах Метрики.')
                    ->hintAction(self::analyticsHintModal(
                        'hint_analytics_yandex_clickmap',
                        'Карта кликов',
                        'filament.shared.analytics-hints.yandex-clickmap',
                    )),
                Toggle::make('analytics_yandex_track_links_enabled')
                    ->label('Отслеживание ссылок')
                    ->default(false)
                    ->helperText('Учёт переходов по внешним ссылкам (trackLinks).')
                    ->hintAction(self::analyticsHintModal(
                        'hint_analytics_yandex_track_links',
                        'Отслеживание ссылок',
                        'filament.shared.analytics-hints.yandex-track-links',
                    )),
                Toggle::make('analytics_yandex_accurate_bounce_enabled')
                    ->label('Точный показатель отказов')
                    ->default(false)
                    ->helperText('Метрика: accurateTrackBounce — отказ не «в один клик», если был интерес к странице.')
                    ->hintAction(self::analyticsHintModal(
                        'hint_analytics_yandex_accurate_bounce',
                        'Точный показатель отказов',
                        'filament.shared.analytics-hints.yandex-accurate-bounce',
                    )),
                Toggle::make('analytics_ga4_enabled')
                    ->label('Google Analytics 4')
                    ->helperText('Включить gtag для GA4.')
                    ->hintAction(self::analyticsHintModal(
                        'hint_analytics_ga4_main',
                        'Google Analytics 4',
                        'filament.shared.analytics-hints.ga4-main',
                    )),
                TextInput::make('analytics_ga4_measurement_id')
                    ->label('Идентификатор GA4')
                    ->helperText('Только код вида G-ABC123DEF4 (как в интерфейсе Google Analytics). Не вставляйте целиком скрипт с сайта.')
                    ->maxLength(32),
            ])->columns(2);
    }

    private static function analyticsHintModal(string $name, string $heading, string $view): Action
    {
        return Action::make($name)
            ->iconButton()
            ->icon('heroicon-m-information-circle')
            ->tooltip('Подробнее')
            ->modalHeading($heading)
            ->modalContent(fn () => View::make($view))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Закрыть')
            ->modalWidth(Width::TwoExtraLarge);
    }
}
