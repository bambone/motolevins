<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Pages;

use App\NotificationCenter\NotificationEventRegistry;
use App\TenantSiteSetup\BookingNotificationsBriefingApplier;
use App\TenantSiteSetup\BookingNotificationsQuestionnaireRepository;
use App\TenantSiteSetup\TenantSiteSetupFeature;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

class TenantSiteSetupBookingNotificationsPage extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'SiteLaunch';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Запись и уведомления (бриф)';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $title = 'Запись и уведомления';

    protected static ?string $slug = 'site-setup-booking-notifications';

    protected string $view = 'filament.tenant.pages.tenant-site-setup-booking-notifications';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        if (! TenantSiteSetupFeature::enabled()) {
            return false;
        }

        if (! Gate::allows('manage_settings') || currentTenant() === null) {
            return false;
        }

        $u = Auth::user();

        return $u !== null && (
            Gate::forUser($u)->allows('manage_scheduling')
            || Gate::forUser($u)->allows('manage_notifications')
            || Gate::forUser($u)->allows('manage_notification_destinations')
            || Gate::forUser($u)->allows('manage_notification_subscriptions')
        );
    }

    public function mount(): void
    {
        abort_unless(Gate::allows('manage_settings'), 403);
        $tenant = currentTenant();
        abort_if($tenant === null, 404);

        $this->data = app(BookingNotificationsQuestionnaireRepository::class)->getMerged($tenant->id);
    }

    public function form(Schema $schema): Schema
    {
        $tenant = currentTenant();
        $schedulingOn = $tenant !== null && $tenant->scheduling_module_enabled;

        $eventOptions = [];
        foreach (NotificationEventRegistry::all() as $def) {
            if (! $schedulingOn && str_starts_with($def->key, 'booking.')) {
                continue;
            }
            $eventOptions[$def->key] = $def->defaultTitle.' ('.$def->key.')';
        }

        return $schema
            ->statePath('data')
            ->components([
                Section::make('Контекст')
                    ->description('Бриф для автоматической настройки групп записи, получателей и правил уведомлений. Подробный перечень вопросов — в документации для гида.')
                    ->schema([
                        TextInput::make('meta_brand_name')
                            ->label('Бренд / название на сайте')
                            ->maxLength(255),
                        TextInput::make('meta_timezone')
                            ->label('Часовой пояс')
                            ->placeholder('Europe/Moscow')
                            ->maxLength(64),
                    ])
                    ->columns(2),
                Section::make('Параметры записи (пресет)')
                    ->description('Используется только если у клиента включён модуль записи и у вас есть право «Запись и расписание».')
                    ->visible(fn (): bool => $schedulingOn && Gate::allows('manage_scheduling'))
                    ->schema([
                        TextInput::make('sched_duration_min')
                            ->label('Длительность слота (мин)')
                            ->numeric()
                            ->minValue(5)
                            ->maxValue(24 * 60),
                        TextInput::make('sched_slot_step_min')
                            ->label('Шаг между слотами (мин)')
                            ->numeric()
                            ->minValue(5),
                        TextInput::make('sched_buffer_before')
                            ->label('Буфер до (мин)')
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('sched_buffer_after')
                            ->label('Буфер после (мин)')
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('sched_horizon_days')
                            ->label('Запись не дальше (дней)')
                            ->numeric()
                            ->minValue(1),
                        TextInput::make('sched_notice_min')
                            ->label('Минимум времени до начала (мин)')
                            ->numeric()
                            ->minValue(0),
                        Toggle::make('sched_requires_confirmation')
                            ->label('Подтверждать заявку вручную')
                            ->default(true),
                    ])
                    ->columns(2),
                Section::make('Получатели уведомлений')
                    ->description('Создаются записи в разделе «Получатели уведомлений». Нужны права на получателей.')
                    ->schema([
                        TextInput::make('dest_email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('dest_telegram_chat_id')
                            ->label('Telegram chat_id')
                            ->helperText('Идентификатор чата или канала для бота.')
                            ->maxLength(128),
                    ])
                    ->columns(2),
                Section::make('События для правил')
                    ->description('Для отмеченных событий будут созданы или обновлены правила с доставкой на указанные получатели.')
                    ->schema([
                        CheckboxList::make('events_enabled')
                            ->label('События')
                            ->options($eventOptions)
                            ->columns(1)
                            ->bulkToggleable(),
                    ]),
            ]);
    }

    public function saveDraft(): void
    {
        $tenant = currentTenant();
        if ($tenant === null) {
            return;
        }

        $state = $this->getSchema('form')->getState();
        app(BookingNotificationsQuestionnaireRepository::class)->save($tenant->id, $state);

        Notification::make()
            ->title('Черновик сохранён')
            ->success()
            ->send();
    }

    public function applyNow(): void
    {
        $tenant = currentTenant();
        $user = Auth::user();
        if ($tenant === null || $user === null) {
            return;
        }

        $applier = app(BookingNotificationsBriefingApplier::class);
        $applier->assertCanApplySomething($user);

        $state = $this->getSchema('form')->getState();
        $result = $applier->apply($tenant, $user, $state);

        Notification::make()
            ->title('Настройки применены')
            ->body(
                'Получателей: '.$result['destinations_created'].', правил: '.$result['subscriptions_created']
                .($result['preset_id'] !== null ? ', пресет #'.$result['preset_id'] : '')
            )
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('saveDraft')
                ->label('Сохранить черновик')
                ->action('saveDraft'),
            Action::make('applyNow')
                ->label('Применить к кабинету')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Применить бриф к настройкам?')
                ->modalDescription('Будут созданы или обновлены пресет записи (если доступен модуль), получатели и правила уведомлений по выбранным событиям.')
                ->action('applyNow'),
        ];
    }
}
