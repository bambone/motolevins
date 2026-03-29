<?php

namespace App\Filament\Platform\Pages;

use App\Filament\Platform\Pages\Concerns\GrantsPlatformPageAccess;
use App\Models\PlatformSetting;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Cache;
use JsonException;
use UnitEnum;

class PlatformMarketingSettingsPage extends Page
{
    use GrantsPlatformPageAccess;

    protected static ?string $navigationLabel = 'Маркетинг и контент';

    protected static ?string $title = 'Маркетинг, SEO и почта форм';

    protected static ?string $slug = 'marketing-settings';

    protected static string|UnitEnum|null $navigationGroup = 'Платформа';

    protected static ?int $navigationSort = 12;

    protected static ?string $panel = 'platform';

    protected string $view = 'filament.pages.platform.marketing-settings';

    public ?array $data = [];

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $overlay = PlatformSetting::get('marketing.config_overlay', []);
        $overlayJson = is_array($overlay) && $overlay !== []
            ? json_encode($overlay, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            : '';

        $this->getSchema('form')->fill([
            'email_contact_form_recipients' => (string) PlatformSetting::get('email.contact_form_recipients', ''),
            'email_default_from_address' => (string) PlatformSetting::get('email.default_from_address', ''),
            'email_default_from_name' => (string) PlatformSetting::get('email.default_from_name', ''),
            'marketing_config_overlay' => $overlayJson,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Почта входящих форм')
                    ->description('Куда уходит уведомление о заявке с маркетингового сайта и от чьего имени отправляется письмо сотрудникам.')
                    ->schema([
                        TextInput::make('email_contact_form_recipients')
                            ->label('Получатели (email.contact_form_recipients)')
                            ->helperText('Один адрес или несколько через запятую. Пусто — fallback из PLATFORM_MARKETING_CONTACT_TO / mail.from.')
                            ->maxLength(2000),
                        TextInput::make('email_default_from_address')
                            ->label('From: адрес (email.default_from_address)')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('email_default_from_name')
                            ->label('From: имя (email.default_from_name)')
                            ->maxLength(255),
                    ]),
                Section::make('Контент лендинга (оверлей)')
                    ->description('JSON поверх config/platform_marketing.php. Ключи совпадают со структурой конфига; перезаписываются рекурсивно.')
                    ->schema([
                        Textarea::make('marketing_config_overlay')
                            ->label('marketing.config_overlay')
                            ->rows(14)
                            ->helperText('Оставьте пустым, чтобы использовать только файл конфигурации.'),
                    ]),
            ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $state = $this->getSchema('form')->getState();

        PlatformSetting::set(
            'email.contact_form_recipients',
            trim((string) ($state['email_contact_form_recipients'] ?? '')),
            'string'
        );
        PlatformSetting::set(
            'email.default_from_address',
            trim((string) ($state['email_default_from_address'] ?? '')),
            'string'
        );
        PlatformSetting::set(
            'email.default_from_name',
            trim((string) ($state['email_default_from_name'] ?? '')),
            'string'
        );

        $rawOverlay = trim((string) ($state['marketing_config_overlay'] ?? ''));
        if ($rawOverlay === '') {
            PlatformSetting::query()->where('key', 'marketing.config_overlay')->delete();
            Cache::forget('platform_settings.marketing.config_overlay');
        } else {
            try {
                $decoded = json_decode($rawOverlay, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                Notification::make()
                    ->title('Некорректный JSON оверлея')
                    ->danger()
                    ->send();

                return;
            }
            if (! is_array($decoded)) {
                Notification::make()
                    ->title('Оверлей должен быть JSON-объектом')
                    ->danger()
                    ->send();

                return;
            }
            PlatformSetting::set('marketing.config_overlay', $decoded, 'json');
        }

        Notification::make()
            ->title('Сохранено')
            ->success()
            ->send();
    }
}
