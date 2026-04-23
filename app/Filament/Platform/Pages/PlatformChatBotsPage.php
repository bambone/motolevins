<?php

namespace App\Filament\Platform\Pages;

use App\Filament\Platform\Pages\Concerns\GrantsPlatformPageAccess;
use App\Models\PlatformSetting;
use App\Services\Platform\PlatformNotificationSettings;
use App\Services\Telegram\TelegramBotContentResolver;
use App\Services\Telegram\TelegramWebhookUrlBuilder;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;
use UnitEnum;

class PlatformChatBotsPage extends Page
{
    use GrantsPlatformPageAccess;

    protected static ?string $navigationLabel = 'Чат-боты';

    protected static ?string $title = 'Чат-боты';

    protected static ?string $slug = 'chat-bots';

    protected static string|UnitEnum|null $navigationGroup = 'Платформа';

    protected static ?int $navigationSort = 13;

    protected static ?string $panel = 'platform';

    protected string $view = 'filament.pages.platform.chat-bots';

    public ?array $data = [];

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->getSchema('form')->fill($this->loadFormState());
    }

    public function form(Schema $schema): Schema
    {
        $webhookUrl = e(app(TelegramWebhookUrlBuilder::class)->telegramWebhookUrl());
        $providersUrl = e(PlatformNotificationProvidersPage::getUrl());

        return $schema
            ->statePath('data')
            ->components([
                Section::make('Telegram')
                    ->description('Тексты ответов на команды в личном чате с ботом. Токен бота задаётся в «Уведомления (провайдеры)».')
                    ->schema([
                        Placeholder::make('token_status')
                            ->label('Токен бота')
                            ->content(fn (): HtmlString => $this->tokenStatusHtml()),
                        Placeholder::make('webhook_hint')
                            ->label('URL webhook для setWebhook')
                            ->content(new HtmlString(
                                '<p class="text-sm text-gray-600 dark:text-gray-400">'
                                .'Принимается только на <strong>публичных</strong> хостах: маркетинг (TENANCY_CENTRAL_DOMAINS), панель платформы (PLATFORM_HOST) или хост из APP_URL. Не на поддомене сайта клиента.'
                                .'</p>'
                                .'<p class="text-sm text-gray-600 dark:text-gray-400 mt-2">'
                                .'<code class="text-xs break-all">'.$webhookUrl.'</code></p>'
                                .'<p class="text-sm text-gray-600 dark:text-gray-400 mt-2">'
                                .'Токен бота — в '
                                .'<a class="text-primary-600 dark:text-primary-400 underline font-medium" href="'.$providersUrl.'">Уведомления (провайдеры)</a>.'
                                .' <code class="text-xs">TELEGRAM_WEBHOOK_SECRET</code> — сгенерируйте сами (например <code class="text-xs">php -r "echo bin2hex(random_bytes(32));"</code>) '
                                .'и передайте то же значение в setWebhook как <code class="text-xs">secret_token</code> и в .env.'
                                .'</p>'
                            ))
                            ->columnSpanFull(),
                        Placeholder::make('webhook_secret_status')
                            ->label('Проверка secret')
                            ->content(fn (): HtmlString => $this->webhookSecretStatusHtml())
                            ->columnSpanFull(),
                        Textarea::make('reply_start')
                            ->label('Ответ на /start')
                            ->rows(5)
                            ->maxLength(4096),
                        Textarea::make('reply_help')
                            ->label('Ответ на /help')
                            ->rows(5)
                            ->maxLength(4096),
                        Textarea::make('reply_status')
                            ->label('Ответ на /status')
                            ->rows(4)
                            ->maxLength(4096),
                        Textarea::make('reply_unknown')
                            ->label('Ответ на неизвестную команду')
                            ->rows(3)
                            ->maxLength(4096),
                        Toggle::make('private_only')
                            ->label('Только личные чаты (рекомендуется)')
                            ->helperText('Если включено, команды в группах игнорируются без ответа.')
                            ->default(true),
                        Toggle::make('webhook_secret_enabled')
                            ->label('Проверять X-Telegram-Bot-Api-Secret-Token')
                            ->helperText('Требует непустой TELEGRAM_WEBHOOK_SECRET в .env и тот же secret в вызове setWebhook.')
                            ->default(false),
                    ]),
                Section::make('VK (скоро)')
                    ->description('Зарезервировано под второй мессенджер: те же смыслы ключей, префикс notification.vk_bot.*')
                    ->schema([
                        Placeholder::make('vk_stub')
                            ->label('')
                            ->content('Настройки VK-бота появятся в следующих версиях.'),
                    ])
                    ->collapsed(),
            ]);
    }

    public function save(): void
    {
        $data = $this->getSchema('form')->getState();

        if (($data['webhook_secret_enabled'] ?? false) && trim((string) config('services.telegram.webhook_secret', '')) === '') {
            Notification::make()
                ->title('Нельзя включить проверку secret')
                ->body('Задайте TELEGRAM_WEBHOOK_SECRET в .env (длинная случайная строка) и с тем же значением укажите secret_token в setWebhook, затем сохраните снова.')
                ->danger()
                ->send();

            return;
        }

        $setString = function (string $key, string $v): void {
            $t = trim($v);
            if ($t === '') {
                PlatformSetting::query()->where('key', $key)->delete();
                Cache::forget('platform_settings.'.$key);

                return;
            }
            PlatformSetting::set($key, $t, 'string');
        };

        $setString(TelegramBotContentResolver::KEY_REPLY_START, (string) ($data['reply_start'] ?? ''));
        $setString(TelegramBotContentResolver::KEY_REPLY_HELP, (string) ($data['reply_help'] ?? ''));
        $setString(TelegramBotContentResolver::KEY_REPLY_STATUS, (string) ($data['reply_status'] ?? ''));
        $setString(TelegramBotContentResolver::KEY_REPLY_UNKNOWN, (string) ($data['reply_unknown'] ?? ''));
        PlatformSetting::set(
            TelegramBotContentResolver::KEY_PRIVATE_ONLY,
            (bool) ($data['private_only'] ?? true),
            'boolean'
        );
        PlatformSetting::set(
            TelegramBotContentResolver::KEY_WEBHOOK_SECRET_ENABLED,
            (bool) ($data['webhook_secret_enabled'] ?? false),
            'boolean'
        );

        Notification::make()->title('Сохранено')->success()->send();
        $this->getSchema('form')->fill($this->loadFormState());
    }

    /**
     * @return array<string, mixed>
     */
    private function loadFormState(): array
    {
        return [
            'reply_start' => (string) PlatformSetting::get(TelegramBotContentResolver::KEY_REPLY_START, ''),
            'reply_help' => (string) PlatformSetting::get(TelegramBotContentResolver::KEY_REPLY_HELP, ''),
            'reply_status' => (string) PlatformSetting::get(TelegramBotContentResolver::KEY_REPLY_STATUS, ''),
            'reply_unknown' => (string) PlatformSetting::get(TelegramBotContentResolver::KEY_REPLY_UNKNOWN, ''),
            'private_only' => (bool) PlatformSetting::get(TelegramBotContentResolver::KEY_PRIVATE_ONLY, true),
            'webhook_secret_enabled' => (bool) PlatformSetting::get(TelegramBotContentResolver::KEY_WEBHOOK_SECRET_ENABLED, false),
        ];
    }

    private function webhookSecretStatusHtml(): HtmlString
    {
        $flagOn = (bool) PlatformSetting::get(TelegramBotContentResolver::KEY_WEBHOOK_SECRET_ENABLED, false);
        $envSet = trim((string) config('services.telegram.webhook_secret', '')) !== '';
        if ($flagOn && ! $envSet) {
            return new HtmlString(
                '<p class="text-sm text-danger-600 dark:text-danger-400 font-medium">'
                .'В настройках включена проверка secret, но TELEGRAM_WEBHOOK_SECRET в .env не задан — защита не работает, запросы с бота будут отклоняться (403) или не пройдут проверку.'
                .'</p>'
            );
        }
        if ($flagOn && $envSet) {
            return new HtmlString(
                '<p class="text-sm text-gray-600 dark:text-gray-400">'
                .'Проверка secret активна: заголовок X-Telegram-Bot-Api-Secret-Token должен совпадать с .env.'
                .'</p>'
            );
        }

        return new HtmlString(
            '<p class="text-sm text-gray-600 dark:text-gray-400">'
            .'Проверка отключена. Включайте после задания TELEGRAM_WEBHOOK_SECRET в .env.'
            .'</p>'
        );
    }

    private function tokenStatusHtml(): HtmlString
    {
        $has = app(PlatformNotificationSettings::class)->telegramBotTokenDecrypted() !== null;
        $text = $has
            ? 'Токен задан (значение скрыто). Изменить: «Уведомления (провайдеры)».'
            : 'Токен не задан. Укажите его в «Уведомления (провайдеры)».';
        $url = e(PlatformNotificationProvidersPage::getUrl());

        return new HtmlString(
            '<p class="text-sm text-gray-700 dark:text-gray-300">'.e($text).'</p>'
            .'<p class="mt-1"><a class="text-primary-600 dark:text-primary-400 underline text-sm font-medium" href="'.$url.'">Открыть провайдеры уведомлений</a></p>'
        );
    }
}
