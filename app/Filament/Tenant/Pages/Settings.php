<?php

namespace App\Filament\Tenant\Pages;

use App\Filament\Forms\Components\TenantPublicImagePicker;
use App\Filament\Shared\TenantAnalyticsFormSchema;
use App\Livewire\Concerns\InteractsWithTenantPublicFilePicker;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Rules\OptionalRussianPhone;
use App\Services\Analytics\AnalyticsSettingsPersistence;
use App\Support\Analytics\AnalyticsSettingsData;
use App\Support\Analytics\AnalyticsSettingsFormMapper;
use App\Support\RussianPhone;
use App\Support\Storage\TenantStorageDisks;
use App\Tenant\StorageQuota\StorageQuotaExceededException;
use App\Tenant\StorageQuota\TenantStorageQuotaService;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\WithFileUploads;
use UnitEnum;

class Settings extends Page
{
    use InteractsWithTenantPublicFilePicker;
    use WithFileUploads;

    /**
     * Form state keys (underscore) → dotted keys passed to TenantSetting / Setting (must match getSettingsData()).
     *
     * @var array<string, string>
     */
    private const FORM_FIELD_TO_SETTING_KEY = [
        'general_site_name' => 'general.site_name',
        'general_domain' => 'general.domain',
        'branding_logo' => 'branding.logo',
        'branding_logo_path' => 'branding.logo_path',
        'branding_primary_color' => 'branding.primary_color',
        'branding_favicon' => 'branding.favicon',
        'branding_favicon_path' => 'branding.favicon_path',
        'branding_hero' => 'branding.hero',
        'branding_hero_path' => 'branding.hero_path',
        'contacts_phone' => 'contacts.phone',
        'contacts_phone_alt' => 'contacts.phone_alt',
        'contacts_email' => 'contacts.email',
        'contacts_whatsapp' => 'contacts.whatsapp',
        'contacts_telegram' => 'contacts.telegram',
        'contacts_address' => 'contacts.address',
        'contacts_hours' => 'contacts.hours',
    ];

    protected static ?string $navigationLabel = 'Настройки';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 10;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $title = 'Настройки сайта';

    protected string $view = 'filament.pages.settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Gate::allows('manage_settings');
    }

    public function mount(): void
    {
        abort_unless(Gate::allows('manage_settings'), 403);
        // manage_settings: в дефолтной матрице pivot только tenant_owner / tenant_admin.
        $this->data = $this->getSettingsData();
    }

    protected function getSettingsData(): array
    {
        $tenant = \currentTenant();
        if ($tenant) {
            return [
                'general_site_name' => TenantSetting::getForTenant($tenant->id, 'general.site_name', $tenant->defaultPublicSiteName()),
                'general_domain' => $this->resolvedGeneralDomainFormValue($tenant),
                'branding_logo' => TenantSetting::getForTenant($tenant->id, 'branding.logo', ''),
                'branding_logo_path' => TenantSetting::getForTenant($tenant->id, 'branding.logo_path', ''),
                'branding_primary_color' => TenantSetting::getForTenant($tenant->id, 'branding.primary_color', '#f59e0b'),
                'branding_favicon' => TenantSetting::getForTenant($tenant->id, 'branding.favicon', ''),
                'branding_favicon_path' => TenantSetting::getForTenant($tenant->id, 'branding.favicon_path', ''),
                'branding_hero' => TenantSetting::getForTenant($tenant->id, 'branding.hero', ''),
                'branding_hero_path' => TenantSetting::getForTenant($tenant->id, 'branding.hero_path', ''),
                'contacts_phone' => RussianPhone::toMasked(TenantSetting::getForTenant($tenant->id, 'contacts.phone', '')),
                'contacts_phone_alt' => RussianPhone::toMasked(TenantSetting::getForTenant($tenant->id, 'contacts.phone_alt', '')),
                'contacts_email' => TenantSetting::getForTenant($tenant->id, 'contacts.email', ''),
                'contacts_whatsapp' => TenantSetting::getForTenant($tenant->id, 'contacts.whatsapp', ''),
                'contacts_telegram' => TenantSetting::getForTenant($tenant->id, 'contacts.telegram', ''),
                'contacts_address' => TenantSetting::getForTenant($tenant->id, 'contacts.address', ''),
                'contacts_hours' => TenantSetting::getForTenant($tenant->id, 'contacts.hours', ''),
                ...AnalyticsSettingsFormMapper::toFormState(
                    app(AnalyticsSettingsPersistence::class)->load((int) $tenant->id)
                ),
            ];
        }

        return [
            'general_site_name' => Setting::get('general.site_name', config('app.name')),
            'general_domain' => Setting::get('general.domain', config('app.url')),
            'branding_logo' => '',
            'branding_primary_color' => '#f59e0b',
            'branding_favicon' => '',
            'contacts_phone' => RussianPhone::toMasked(Setting::get('contacts.phone', '')),
            'contacts_phone_alt' => RussianPhone::toMasked(Setting::get('contacts.phone_alt', '')),
            'contacts_email' => Setting::get('contacts.email', ''),
            'contacts_whatsapp' => Setting::get('contacts.whatsapp', ''),
            'contacts_telegram' => Setting::get('contacts.telegram', ''),
            'contacts_address' => Setting::get('contacts.address', ''),
            'contacts_hours' => Setting::get('contacts.hours', ''),
            ...AnalyticsSettingsFormMapper::toFormState(AnalyticsSettingsData::defaultEmpty()),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Общие')
                    ->description('Базовая информация о сайте для посетителей. Пока поля пустые в БД, подставляются название/бренд клиента и домен (текущий активный или основной), а не настройки лендинга платформы.')
                    ->schema([
                        TextInput::make('general_site_name')
                            ->label('Название сайта')
                            ->helperText('Показывается в шапке, заголовках и письмах, если тема не задаёт иначе.')
                            ->placeholder('Например: MotoLevins Сочи'),
                        TextInput::make('general_domain')
                            ->label('Основной URL сайта')
                            ->url()
                            ->nullable()
                            ->helperText(
                                'Канонический адрес публичного сайта (https://…). Если оставить пустым и сохранить — используется автоматический адрес из доменов клиента '
                                .'(как у карточки «Свой домен» / основной домен), без дублирования в БД. Явный URL нужен, если сайт должен ссылаться с другого базового адреса.'
                            ),
                    ])->columns(2),

                Section::make('Брендинг')
                    ->description('Файлы сохраняются в storage (путь привязан к ID клиента). Обычно достаточно загрузки слева; справа — запасной внешний URL (редко). Если загружен файл, он имеет приоритет над URL.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TenantPublicImagePicker::make('branding_logo_path')
                                    ->label('Логотип (файл)')
                                    ->uploadSlotSelector('[data-settings-tenant-upload-input]')
                                    ->uploadPublicSiteSubdirectory('site/logo')
                                    ->helperText('PNG, JPG, WebP. До 4 МБ; можно выбрать из каталога.'),
                                TextInput::make('branding_logo')
                                    ->label('Логотип (URL, запасной)')
                                    ->url()
                                    ->placeholder('https://...')
                                    ->helperText('Только если файл не задан.'),
                            ]),
                        TextInput::make('branding_primary_color')
                            ->label('Основной цвет')
                            ->type('color')
                            ->columnSpanFull()
                            ->helperText('Акцентные кнопки и ссылки на сайте.'),
                        Grid::make(2)
                            ->schema([
                                TenantPublicImagePicker::make('branding_favicon_path')
                                    ->label('Favicon (файл)')
                                    ->uploadSlotSelector('[data-settings-tenant-upload-input]')
                                    ->uploadPublicSiteSubdirectory('site/favicon')
                                    ->helperText('PNG, ICO, SVG. До 4 МБ; для иконки лучше заранее оптимизировать файл.'),
                                TextInput::make('branding_favicon')
                                    ->label('Favicon (URL, запасной)')
                                    ->url()
                                    ->placeholder('https://...')
                                    ->helperText('Только если файл не задан.'),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TenantPublicImagePicker::make('branding_hero_path')
                                    ->label('Hero / OG-изображение (файл)')
                                    ->uploadSlotSelector('[data-settings-tenant-upload-input]')
                                    ->uploadPublicSiteSubdirectory('site/hero')
                                    ->helperText('Крупное изображение для шапки или соцсетей; вывод задаёт тема.'),
                                TextInput::make('branding_hero')
                                    ->label('Hero / OG (URL, запасной)')
                                    ->url()
                                    ->nullable()
                                    ->helperText('Только если файл не задан.'),
                            ]),
                    ])
                    ->columns(1)
                    ->visible(fn () => \currentTenant() !== null),

                Section::make('Контакты')
                    ->description('Телефоны и мессенджеры обычно выводятся в шапке, подвале и на странице контактов.')
                    ->schema([
                        TextInput::make('contacts_phone')
                            ->label('Телефон')
                            ->mask('+7 (999) 999-99-99')
                            ->placeholder('+7 (___) ___-__-__')
                            ->rules([new OptionalRussianPhone])
                            ->helperText('Маска для российского номера. После сохранения в базе хранится в виде +7XXXXXXXXXX.'),
                        TextInput::make('contacts_phone_alt')
                            ->label('Дополнительный телефон')
                            ->mask('+7 (999) 999-99-99')
                            ->placeholder('+7 (___) ___-__-__')
                            ->rules([new OptionalRussianPhone]),
                        TextInput::make('contacts_email')->label('Email')->email()->placeholder('hello@example.com'),
                        TextInput::make('contacts_whatsapp')
                            ->label('WhatsApp')
                            ->placeholder('Номер или ссылка https://wa.me/…')
                            ->helperText('Без маски: можно номер или полную ссылку WhatsApp.'),
                        TextInput::make('contacts_telegram')->label('Telegram')->placeholder('@username или ссылка t.me/…'),
                        Textarea::make('contacts_address')->label('Адрес')->rows(2),
                        Textarea::make('contacts_hours')->label('Часы работы')->rows(2)->placeholder('Например: Пн–Вс 9:00–21:00'),
                    ])->columns(2),

                TenantAnalyticsFormSchema::section(fn (): bool => \currentTenant() !== null),
            ]);
    }

    public function save(): void
    {
        $data = $this->getSchema('form')->getState();
        $tenant = \currentTenant();

        if ($tenant) {
            try {
                $this->assertBrandingUploadsWithinQuota($tenant, $data);
            } catch (StorageQuotaExceededException $e) {
                Notification::make()
                    ->title($e->getMessage())
                    ->danger()
                    ->send();

                return;
            }
        }

        if ($tenant) {
            try {
                $persistence = app(AnalyticsSettingsPersistence::class);
                $before = $persistence->load((int) $tenant->id);
                $new = AnalyticsSettingsFormMapper::toValidatedData($data);
                $persistence->save((int) $tenant->id, $new, Auth::user(), $before);
            } catch (ValidationException $e) {
                foreach ($e->errors() as $messages) {
                    Notification::make()
                        ->title($messages[0] ?? 'Ошибка валидации')
                        ->danger()
                        ->send();
                }

                return;
            }
        }

        if ($tenant !== null && array_key_exists('general_domain', $data)) {
            $raw = trim((string) ($data['general_domain'] ?? ''));
            if ($raw === '' || ! filter_var($raw, FILTER_VALIDATE_URL)) {
                TenantSetting::forgetForTenant((int) $tenant->id, 'general.domain');
            } else {
                TenantSetting::setForTenant((int) $tenant->id, 'general.domain', rtrim($raw, '/'));
            }
            unset($data['general_domain']);
        }

        foreach ($data as $field => $value) {
            if (! array_key_exists($field, self::FORM_FIELD_TO_SETTING_KEY)) {
                continue;
            }

            if (is_array($value)) {
                continue;
            }

            $settingKey = self::FORM_FIELD_TO_SETTING_KEY[$field];
            $stored = $value === null ? '' : (string) $value;

            if (in_array($field, ['contacts_phone', 'contacts_phone_alt'], true)) {
                $normalized = RussianPhone::normalize($stored);
                $stored = $normalized ?? '';
            }

            if ($tenant) {
                TenantSetting::setForTenant($tenant->id, $settingKey, $stored);
            } else {
                Setting::set($settingKey, $stored);
            }
        }

        Notification::make()
            ->title('Настройки сохранены')
            ->success()
            ->send();
    }

    /**
     * @return array<string, string>
     */
    public static function formFieldToSettingKeyMap(): array
    {
        return self::FORM_FIELD_TO_SETTING_KEY;
    }

    /**
     * Effective public base URL for the form: explicit tenant_settings.general.domain if valid, else same fallback as runtime (active request host, primary domain, app.url).
     */
    private function resolvedGeneralDomainFormValue(Tenant $tenant): string
    {
        $stored = trim((string) TenantSetting::getForTenant($tenant->id, 'general.domain', ''));
        if ($stored !== '' && filter_var($stored, FILTER_VALIDATE_URL)) {
            return rtrim($stored, '/');
        }

        return rtrim($tenant->defaultPublicSiteUrl(), '/');
    }

    private function assertBrandingUploadsWithinQuota(Tenant $tenant, array $formData): void
    {
        if (! TenantStorageQuotaService::isQuotaEnforcementActive()) {
            return;
        }

        $disk = Storage::disk(TenantStorageDisks::publicDiskName());
        $before = $this->getSettingsData();
        $fields = ['branding_logo_path', 'branding_favicon_path', 'branding_hero_path'];
        $sum = 0;
        foreach ($fields as $field) {
            $new = isset($formData[$field]) ? (string) $formData[$field] : '';
            $old = isset($before[$field]) ? (string) $before[$field] : '';
            if ($new === '' || $new === $old) {
                continue;
            }
            if (! $disk->exists($new)) {
                continue;
            }
            $sum += (int) $disk->size($new);
        }
        if ($sum > 0) {
            app(TenantStorageQuotaService::class)->assertCanStoreBytes($tenant, $sum, 'branding_upload');
        }
    }
}
