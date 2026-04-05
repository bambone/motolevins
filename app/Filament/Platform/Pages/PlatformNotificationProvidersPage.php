<?php

namespace App\Filament\Platform\Pages;

use App\Filament\Platform\Pages\Concerns\GrantsPlatformPageAccess;
use App\Services\Platform\PlatformNotificationSettings;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use UnitEnum;

class PlatformNotificationProvidersPage extends Page
{
    use GrantsPlatformPageAccess;

    protected static ?string $navigationLabel = 'Уведомления (провайдеры)';

    protected static ?string $title = 'Провайдеры уведомлений';

    protected static ?string $slug = 'notification-providers';

    protected static ?string $panel = 'platform';

    protected static string|UnitEnum|null $navigationGroup = 'Система';

    protected static ?int $navigationSort = 35;

    protected string $view = 'filament.pages.platform.notification-providers';

    public function mount(PlatformNotificationSettings $settings): void
    {
        $this->getSchema('form')->fill([
            'channel_email_enabled' => $settings->isChannelEnabled('email'),
            'channel_telegram_enabled' => $settings->isChannelEnabled('telegram'),
            'channel_webhook_enabled' => $settings->isChannelEnabled('webhook'),
            'channel_web_push_enabled' => $settings->isChannelEnabled('web_push'),
            'telegram_bot_token' => '',
            'vapid_public' => $settings->vapidPublicKey() ?? '',
            'vapid_private' => '',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Каналы (kill switch)')
                    ->schema([
                        Toggle::make('channel_email_enabled')->label('Email включён'),
                        Toggle::make('channel_telegram_enabled')->label('Telegram включён'),
                        Toggle::make('channel_webhook_enabled')->label('Webhook включён'),
                        Toggle::make('channel_web_push_enabled')->label('Web Push включён'),
                    ])->columns(2),
                Section::make('Telegram')
                    ->schema([
                        TextInput::make('telegram_bot_token')
                            ->label('Bot token')
                            ->password()
                            ->revealable()
                            ->helperText('Оставьте пустым, чтобы не менять сохранённый токен.'),
                    ]),
                Section::make('Web Push (VAPID)')
                    ->schema([
                        TextInput::make('vapid_public')->label('Публичный ключ'),
                        TextInput::make('vapid_private')
                            ->label('Приватный ключ')
                            ->password()
                            ->revealable()
                            ->helperText('Оставьте пустым, чтобы не менять приватный ключ.'),
                    ]),
            ]);
    }

    public function save(PlatformNotificationSettings $settings): void
    {
        $data = $this->getSchema('form')->getState();
        $settings->setChannelEnabled('email', (bool) ($data['channel_email_enabled'] ?? false));
        $settings->setChannelEnabled('telegram', (bool) ($data['channel_telegram_enabled'] ?? false));
        $settings->setChannelEnabled('webhook', (bool) ($data['channel_webhook_enabled'] ?? false));
        $settings->setChannelEnabled('web_push', (bool) ($data['channel_web_push_enabled'] ?? false));

        $token = trim((string) ($data['telegram_bot_token'] ?? ''));
        if ($token !== '') {
            $settings->setTelegramBotToken($token);
        }

        $pub = trim((string) ($data['vapid_public'] ?? ''));
        $priv = trim((string) ($data['vapid_private'] ?? ''));
        if ($pub !== '' && $priv !== '') {
            $settings->setVapidKeypair($pub, $priv);
        } elseif ($pub !== '' && $settings->vapidPrivateKeyDecrypted() === null) {
            Notification::make()->title('Укажите приватный VAPID ключ или оставьте оба поля пустыми.')->danger()->send();

            return;
        }

        Notification::make()->title('Сохранено')->success()->send();
    }
}
