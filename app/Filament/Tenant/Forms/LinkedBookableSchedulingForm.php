<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Forms;

use App\Filament\Support\FilamentInlineMarkdown;
use App\Filament\Tenant\Resources\SchedulingTargetResource;
use App\Models\AvailabilityRule;
use App\Models\Motorcycle;
use App\Models\RentalUnit;
use App\Models\SchedulingTarget;
use App\Scheduling\Enums\AvailabilityRuleType;
use App\Scheduling\Enums\CalendarUsageMode;
use App\Scheduling\Enums\SchedulingScope;
use App\Scheduling\Enums\SchedulingTargetType;
use App\Scheduling\LinkedBookableServiceManager;
use App\Scheduling\SchedulingEntitlementService;
use Closure;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Js;
use Illuminate\Validation\ValidationException;

/**
 * Поля linked online-booking для карточек Motorcycle / RentalUnit (только edit для полной формы).
 */
final class LinkedBookableSchedulingForm
{
    /**
     * Кэш на один HTTP-запрос: бейдж вкладки и summary вызывают проверки дважды за рендер.
     *
     * @var array<string, list<string>>
     */
    private static array $linkedSetupWarningsMemo = [];

    public const string TAB_KEY_MAIN = 'main';

    public const string TAB_KEY_ONLINE_BOOKING = 'online-booking';

    public const string TAB_KEY_FLEET_UNITS = 'fleet-units';

    public const string MOTORCYCLE_TAB_QUERY_KEY = 'moto_edit_tab';

    public const string RENTAL_UNIT_TAB_QUERY_KEY = 'rental_unit_edit_tab';

    /**
     * Значение {@see self::MOTORCYCLE_TAB_QUERY_KEY} для текущего запроса.
     * Livewire POST на {@code /livewire/.../update} обычно без query string — тогда берём вкладку из {@code Referer}.
     */
    public static function resolveMotorcycleEditTabQueryFromRequest(?Request $request = null): string
    {
        $request ??= request();
        $key = self::MOTORCYCLE_TAB_QUERY_KEY;
        $fromQuery = $request->query($key);
        if (is_string($fromQuery) && $fromQuery !== '') {
            return mb_strtolower(rawurldecode($fromQuery));
        }

        $referer = (string) $request->header('Referer', '');
        if ($referer === '') {
            return '';
        }

        $query = parse_url($referer, PHP_URL_QUERY);
        if (! is_string($query) || $query === '') {
            return '';
        }

        parse_str($query, $params);
        $tab = $params[$key] ?? '';

        return is_string($tab) && $tab !== '' ? mb_strtolower(rawurldecode($tab)) : '';
    }

    /**
     * Вкладки через {@see Tabs::persistTabInQueryString()} — активная вкладка только в Alpine.
     * При ошибках валидации по linked-полям переключаем вкладку и query в браузере, чтобы пользователь увидел сообщения.
     */
    public static function jsSyncBrowserTabToOnlineBooking(string|int $livewireComponentId, string $queryParamKey): string
    {
        $id = Js::from((string) $livewireComponentId);
        $tab = Js::from(self::TAB_KEY_ONLINE_BOOKING);
        $queryKey = Js::from($queryParamKey);

        return <<<JS
(function () {
    const id = {$id};
    const tab = {$tab};
    const queryKey = {$queryKey};
    const root = document.querySelector('[wire\\\\:id="' + id + '"]');
    const tabsEl = root?.querySelector('.fi-sc-tabs');
    if (tabsEl && window.Alpine && typeof Alpine.\$data === 'function') {
        const data = Alpine.\$data(tabsEl);
        if (data && Object.prototype.hasOwnProperty.call(data, 'tab')) {
            data.tab = tab;
        }
    }
    const u = new URL(window.location.href);
    u.searchParams.set(queryKey, tab);
    window.history.replaceState(null, document.title, u.toString());
})();
JS;
    }

    public static function schedulingForbidden(): bool
    {
        return ! Gate::allows('manage_scheduling');
    }

    public static function schedulingUiVisible(): bool
    {
        return ! self::schedulingForbidden();
    }

    public static function schedulingLocked(): bool
    {
        if (self::schedulingForbidden()) {
            return false;
        }

        $tenant = currentTenant();
        if ($tenant === null) {
            return true;
        }

        return ! app(SchedulingEntitlementService::class)->tenantCanConfigureLinkedScheduling($tenant);
    }

    public static function schedulingFormEditable(): bool
    {
        return self::schedulingUiVisible() && ! self::schedulingLocked();
    }

    /**
     * @deprecated Используйте {@see self::schedulingFormEditable()}
     */
    public static function schedulingSectionVisible(): bool
    {
        return self::schedulingFormEditable();
    }

    public static function motorcycleOnlineBookingTab(): Tab
    {
        return Tab::make('Онлайн-запись')
            ->id(self::TAB_KEY_ONLINE_BOOKING)
            ->icon(fn (): ?string => self::schedulingLocked() ? 'heroicon-o-lock-closed' : null)
            ->badge(fn (Get $get, ?Model $record): ?string => self::schedulingTabBadgeLabel($get, $record))
            ->badgeColor(fn (Get $get, ?Model $record): ?string => self::schedulingTabBadgeColor($get, $record))
            ->hiddenOn('create')
            ->visible(fn (): bool => self::schedulingUiVisible())
            ->schema(fn (): array => self::motorcycleOnlineBookingTabSchema())
            ->columnSpan(['default' => 12, 'lg' => 12]);
    }

    public static function rentalUnitOnlineBookingTab(): Tab
    {
        return Tab::make('Онлайн-запись')
            ->id(self::TAB_KEY_ONLINE_BOOKING)
            ->icon(fn (): ?string => self::schedulingLocked() ? 'heroicon-o-lock-closed' : null)
            ->badge(fn (Get $get, ?Model $record): ?string => self::schedulingTabBadgeLabel($get, $record))
            ->badgeColor(fn (Get $get, ?Model $record): ?string => self::schedulingTabBadgeColor($get, $record))
            ->hiddenOn('create')
            ->visible(fn (): bool => self::schedulingUiVisible())
            ->schema(fn (): array => self::rentalUnitOnlineBookingTabSchema())
            ->columnSpan(['default' => 12, 'lg' => 12]);
    }

    public static function motorcycleCreateNotice(): Placeholder
    {
        return Placeholder::make('linked_scheduling_create_notice_moto')
            ->label('Онлайн-запись')
            ->hiddenOn('edit')
            ->visible(fn (): bool => self::schedulingUiVisible())
            ->content(new HtmlString(
                '<p class="text-sm text-gray-600 dark:text-gray-400">Сначала сохраните карточку, затем включите и настройте онлайн-запись на вкладке «Онлайн-запись».</p>'
            ))
            ->columnSpan(['default' => 12, 'lg' => 12]);
    }

    /**
     * Значения linked_* для заполнения формы (edit мотоцикла / изолированный блок).
     *
     * @return array<string, mixed>
     */
    public static function linkedFormDataForMotorcycle(Motorcycle $motorcycle): array
    {
        if (! self::schedulingSectionVisible()) {
            return [];
        }

        $manager = app(LinkedBookableServiceManager::class);
        $service = $manager->findLinkedForMotorcycle($motorcycle, SchedulingScope::Tenant);

        if ($service === null) {
            return [
                'linked_booking_enabled' => false,
                'linked_sync_title_from_source' => true,
                'linked_duration_minutes' => 60,
                'linked_slot_step_minutes' => 15,
                'linked_buffer_before_minutes' => 0,
                'linked_buffer_after_minutes' => 0,
                'linked_min_booking_notice_minutes' => 120,
                'linked_max_booking_horizon_days' => 60,
                'linked_requires_confirmation' => true,
                'linked_sort_weight' => 0,
            ];
        }

        $target = $service->schedulingTarget;

        return [
            'linked_booking_enabled' => $service->is_active && ($target?->scheduling_enabled ?? false),
            'linked_sync_title_from_source' => $service->sync_title_from_source,
            'linked_duration_minutes' => $service->duration_minutes,
            'linked_slot_step_minutes' => $service->slot_step_minutes,
            'linked_buffer_before_minutes' => $service->buffer_before_minutes,
            'linked_buffer_after_minutes' => $service->buffer_after_minutes,
            'linked_min_booking_notice_minutes' => $service->min_booking_notice_minutes,
            'linked_max_booking_horizon_days' => $service->max_booking_horizon_days,
            'linked_requires_confirmation' => $service->requires_confirmation,
            'linked_sort_weight' => $service->sort_weight,
        ];
    }

    public static function rentalUnitCreateNotice(): Placeholder
    {
        return Placeholder::make('linked_scheduling_create_notice_unit')
            ->label('Онлайн-запись')
            ->hiddenOn('edit')
            ->visible(fn (): bool => self::schedulingUiVisible())
            ->content(new HtmlString(
                '<p class="text-sm text-gray-600 dark:text-gray-400">Сначала сохраните карточку, затем включите и настройте онлайн-запись на вкладке «Онлайн-запись».</p>'
            ))
            ->columnSpan(['default' => 12, 'lg' => 12]);
    }

    public static function motorcycleSummarySection(): Section
    {
        return Section::make()
            ->compact()
            ->visible(fn (): bool => self::schedulingUiVisible())
            ->visibleOn('edit')
            ->schema([
                Placeholder::make('linked_scheduling_summary_moto')
                    ->hiddenLabel()
                    ->content(function (Get $get, ?Motorcycle $record): HtmlString {
                        return self::buildSummaryHtml(
                            record: $record,
                            get: fn (string $key): mixed => $get($key),
                            findTarget: function (?Motorcycle $m) {
                                if ($m === null || ! $m->exists) {
                                    return null;
                                }

                                return app(LinkedBookableServiceManager::class)
                                    ->findLinkedForMotorcycle($m, SchedulingScope::Tenant)
                                    ?->schedulingTarget;
                            },
                        );
                    })
                    ->columnSpanFull(),
            ])
            ->columns(1)
            ->columnSpan(['default' => 12, 'lg' => 12]);
    }

    public static function rentalUnitSummarySection(): Section
    {
        return Section::make()
            ->compact()
            ->visible(fn (): bool => self::schedulingUiVisible())
            ->visibleOn('edit')
            ->schema([
                Placeholder::make('linked_scheduling_summary_unit')
                    ->hiddenLabel()
                    ->content(function (Get $get, ?RentalUnit $record): HtmlString {
                        return self::buildSummaryHtml(
                            record: $record,
                            get: fn (string $key): mixed => $get($key),
                            findTarget: function (?RentalUnit $u) {
                                if ($u === null || ! $u->exists) {
                                    return null;
                                }

                                return app(LinkedBookableServiceManager::class)
                                    ->findLinkedForRentalUnit($u, SchedulingScope::Tenant)
                                    ?->schedulingTarget;
                            },
                        );
                    })
                    ->columnSpanFull(),
            ])
            ->columns(1)
            ->columnSpan(['default' => 12, 'lg' => 12]);
    }

    public static function motorcycleEditSummaryHtml(Motorcycle $m): HtmlString
    {
        $data = self::linkedFormDataForMotorcycle($m);
        $get = fn (string $key): mixed => $data[$key] ?? null;

        return self::buildSummaryHtml(
            record: $m,
            get: $get,
            findTarget: function (?Motorcycle $record) {
                if ($record === null || ! $record->exists) {
                    return null;
                }

                return app(LinkedBookableServiceManager::class)
                    ->findLinkedForMotorcycle($record, SchedulingScope::Tenant)
                    ?->schedulingTarget;
            },
        );
    }

    /**
     * @param  callable(string): mixed  $get
     * @param  callable(Motorcycle|RentalUnit|null): ?SchedulingTarget  $findTarget
     */
    private static function buildSummaryHtml(
        Motorcycle|RentalUnit|null $record,
        callable $get,
        callable $findTarget,
    ): HtmlString {
        if (self::schedulingLocked()) {
            $body = '<p class="text-sm leading-snug text-gray-600 dark:text-gray-400">Онлайн-запись недоступна на текущем тарифе. Обратитесь к администратору платформы, чтобы включить модуль записи.</p>';

            return new HtmlString(
                self::summaryCardShell(
                    stateKind: 'locked',
                    titleLine: 'Онлайн-запись',
                    badgeHtml: self::summaryBadge('НЕДОСТУПНО', 'gray'),
                    bodyHtml: $body,
                    actionsHtml: '',
                )
            );
        }

        $enabled = (bool) $get('linked_booking_enabled');
        $duration = $get('linked_duration_minutes');
        $step = $get('linked_slot_step_minutes');
        $confirm = (bool) $get('linked_requires_confirmation');

        $setupWarnings = self::linkedSchedulingSetupWarnings($get, $record);
        $hasSetupProblems = $enabled && $setupWarnings !== [];

        $metrics = '<p class="mt-1.5 text-sm leading-snug text-gray-600 dark:text-gray-400">'
            .'<span class="font-medium text-gray-800 dark:text-gray-200">Слот:</span> '.e((string) ($duration ?? '—')).' мин'
            .' <span class="text-gray-400 dark:text-gray-500" aria-hidden="true">•</span> '
            .'<span class="font-medium text-gray-800 dark:text-gray-200">Шаг:</span> '.e((string) ($step ?? '—')).' мин'
            .' <span class="text-gray-400 dark:text-gray-500" aria-hidden="true">•</span> '
            .'<span class="font-medium text-gray-800 dark:text-gray-200">Подтверждение:</span> '.e($confirm ? 'вручную' : 'автоматически')
            .'</p>';

        $warningsBlock = '';
        if ($hasSetupProblems) {
            $primaryWarning = e($setupWarnings[0] ?? '');
            $rest = array_slice($setupWarnings, 1);
            $warningsBlock = '<div class="mt-1.5 text-sm leading-snug text-warning-800 dark:text-warning-200">'
                .'<p class="flex gap-2"><span class="shrink-0" aria-hidden="true">⚠️</span><span>'.$primaryWarning.'</span></p>';
            if ($rest !== []) {
                $warningsBlock .= '<ul class="mt-1.5 list-inside list-disc space-y-0.5 pl-1 text-xs opacity-95">';
                foreach ($rest as $w) {
                    $warningsBlock .= '<li>'.e($w).'</li>';
                }
                $warningsBlock .= '</ul>';
            }
            $warningsBlock .= '</div>';
        }

        $target = $findTarget($record);
        $targetEditUrl = $target !== null
            ? SchedulingTargetResource::getUrl('edit', ['record' => $target->id])
            : null;

        if (! $enabled) {
            $hint = '<p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Откройте вкладку «Онлайн-запись», включите запись и сохраните блок «Онлайн-запись».</p>';

            return new HtmlString(
                self::summaryCardShell(
                    stateKind: 'off',
                    titleLine: 'Онлайн-запись',
                    badgeHtml: self::summaryBadge('ВЫКЛЮЧЕНА', 'gray'),
                    bodyHtml: '<p class="text-sm leading-snug text-gray-600 dark:text-gray-400">Клиенты не могут записаться онлайн.</p>'.$hint,
                    actionsHtml: '',
                )
            );
        }

        if ($hasSetupProblems) {
            $targetBtn = $targetEditUrl !== null
                ? '<a href="'.e($targetEditUrl).'" class="linked-scheduling-summary-btn-primary fi-btn inline-flex items-center justify-center gap-x-2 rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-semibold text-white shadow-md ring-2 ring-primary-500/35 outline-none transition duration-75 hover:bg-primary-500 dark:ring-primary-400/45">Открыть цель записи</a>'
                : '';
            $hint = '<p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Сохраните блок «Онлайн-запись» после изменений или донастройте цель записи.</p>';

            return new HtmlString(
                self::summaryCardShell(
                    stateKind: 'warning',
                    titleLine: 'Онлайн-запись',
                    badgeHtml: self::summaryBadge('ТРЕБУЕТ НАСТРОЙКИ', 'warning'),
                    bodyHtml: $warningsBlock.$hint,
                    actionsHtml: $targetBtn !== '' ? '<div class="mt-3 flex flex-wrap items-center gap-2">'.$targetBtn.'</div>' : '',
                )
            );
        }

        $hint = '<p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Сохраните блок «Онлайн-запись», если меняли параметры ниже.</p>';

        return new HtmlString(
            self::summaryCardShell(
                stateKind: 'on',
                titleLine: 'Онлайн-запись',
                badgeHtml: self::summaryBadge('АКТИВНА', 'success'),
                bodyHtml: $metrics.$hint,
                actionsHtml: '',
            )
        );
    }

    private static function summaryBadge(string $label, string $kind): string
    {
        $labelE = e($label);
        $classes = match ($kind) {
            'success' => 'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30',
            'warning' => 'bg-warning-50 text-warning-700 ring-warning-600/20 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/30',
            default => 'bg-gray-50 text-gray-600 ring-gray-600/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/30',
        };

        return '<span class="fi-badge inline-flex items-center rounded-md px-2 py-1 text-xs font-semibold uppercase tracking-wide ring-1 ring-inset '.$classes.'">'.$labelE.'</span>';
    }

    /**
     * @param  'locked'|'off'|'on'|'warning'  $stateKind
     */
    private static function summaryCardShell(
        string $stateKind,
        string $titleLine,
        string $badgeHtml,
        string $bodyHtml,
        string $actionsHtml,
    ): string {
        $border = match ($stateKind) {
            'on' => 'border-success-600/20 dark:border-success-400/25',
            'warning' => 'border-warning-600/25 dark:border-warning-400/30',
            default => 'border-gray-950/10 dark:border-white/10',
        };

        $bg = match ($stateKind) {
            'on' => 'bg-success-50/60 dark:bg-success-500/[0.07]',
            'warning' => 'bg-warning-50/70 dark:bg-warning-500/[0.08]',
            'locked' => 'bg-gray-50 dark:bg-[#12141c]',
            default => 'bg-gray-50 dark:bg-[#12141c]',
        };

        return '<div class="linked-scheduling-summary linked-scheduling-summary--'.$stateKind.' rounded-lg border p-3 shadow-sm '.$border.' '.$bg.'">'
            .'<div class="flex flex-wrap items-center gap-x-2 gap-y-1">'
            .'<span class="text-sm font-semibold text-gray-950 dark:text-white">'.e($titleLine).'</span>'
            .$badgeHtml
            .'</div>'
            .$bodyHtml
            .$actionsHtml
            .'</div>';
    }

    /**
     * @param  callable(string): mixed  $get
     * @return list<string>
     */
    private static function linkedSchedulingSetupWarnings(callable $get, ?Model $record): array
    {
        if (! $record instanceof Motorcycle && ! $record instanceof RentalUnit) {
            return [];
        }

        if (! $record->exists) {
            return [];
        }

        $enabled = (bool) $get('linked_booking_enabled');
        $memoKey = self::linkedSetupWarningsMemoKey($record, $enabled);
        if (array_key_exists($memoKey, self::$linkedSetupWarningsMemo)) {
            return self::$linkedSetupWarningsMemo[$memoKey];
        }

        if (! $enabled) {
            return self::$linkedSetupWarningsMemo[$memoKey] = [];
        }

        $svc = match (true) {
            $record instanceof Motorcycle => app(LinkedBookableServiceManager::class)->findLinkedForMotorcycle($record, SchedulingScope::Tenant),
            $record instanceof RentalUnit => app(LinkedBookableServiceManager::class)->findLinkedForRentalUnit($record, SchedulingScope::Tenant),
            default => null,
        };

        if ($svc === null || ! $svc->is_active) {
            return self::$linkedSetupWarningsMemo[$memoKey] = ['Сохраните блок «Онлайн-запись», чтобы включить запись для клиентов.'];
        }

        $target = $svc->schedulingTarget;
        if ($target === null) {
            return self::$linkedSetupWarningsMemo[$memoKey] = ['Цель записи ещё не создана. Сохраните блок «Онлайн-запись» или откройте эту вкладку снова.'];
        }

        $warnings = [];

        if (! $target->scheduling_enabled) {
            $warnings[] = 'Запись на уровне цели выключена.';
        }

        if ($target->calendar_usage_mode === CalendarUsageMode::Disabled) {
            $warnings[] = 'Календарь не подключён — занятость может не учитываться.';
        }

        // Не вызывать loadMissing(['schedulingTarget.schedulingResources']): при большом pivot
        // Laravel собирает огромную Collection и может исчерпать память (см. Eloquent\Collection::loadMissingRelation).
        if ($target->schedulingResources()->doesntExist()) {
            $warnings[] = 'Не выбран ресурс записи для этой цели.';
        } elseif (! self::targetHasActiveAvailabilityRules($target)) {
            $warnings[] = 'Нет активного расписания (правил доступности).';
        }

        return self::$linkedSetupWarningsMemo[$memoKey] = $warnings;
    }

    private static function linkedSetupWarningsMemoKey(Motorcycle|RentalUnit $record, bool $enabled): string
    {
        $kind = $record instanceof Motorcycle ? 'm' : 'u';

        return $kind.'|'.$record->getKey().'|'.($enabled ? '1' : '0');
    }

    private static function targetHasActiveAvailabilityRules(SchedulingTarget $target): bool
    {
        $serviceId = $target->target_type === SchedulingTargetType::BookableService
            ? $target->target_id
            : null;

        return AvailabilityRule::query()
            ->where('is_active', true)
            ->where('rule_type', AvailabilityRuleType::WeeklyOpen)
            ->where(function ($q) use ($target): void {
                $q->whereNull('applies_to_scheduling_target_id')
                    ->orWhere('applies_to_scheduling_target_id', $target->id);
            })
            ->when(
                $serviceId !== null,
                fn ($q) => $q->where(function ($q2) use ($serviceId): void {
                    $q2->whereNull('applies_to_bookable_service_id')
                        ->orWhere('applies_to_bookable_service_id', $serviceId);
                }),
            )
            ->whereExists(function ($q) use ($target): void {
                $q->selectRaw('1')
                    ->from('scheduling_target_resource')
                    ->whereColumn(
                        'scheduling_target_resource.scheduling_resource_id',
                        'availability_rules.scheduling_resource_id',
                    )
                    ->where('scheduling_target_resource.scheduling_target_id', $target->id);
            })
            ->exists();
    }

    private static function schedulingTabBadgeLabel(Get $get, ?Model $record): ?string
    {
        if (self::schedulingLocked()) {
            return 'Недоступно';
        }

        if (! (bool) $get('linked_booking_enabled')) {
            return 'Выкл';
        }

        if (self::linkedSchedulingSetupWarnings($get, $record) !== []) {
            return '!';
        }

        return 'Вкл';
    }

    private static function schedulingTabBadgeColor(Get $get, ?Model $record): ?string
    {
        if (self::schedulingLocked()) {
            return 'gray';
        }

        if (! (bool) $get('linked_booking_enabled')) {
            return 'gray';
        }

        if (self::linkedSchedulingSetupWarnings($get, $record) !== []) {
            return 'warning';
        }

        return 'success';
    }

    /**
     * @return list<Component>
     */
    private static function motorcycleOnlineBookingTabSchema(): array
    {
        if (self::schedulingLocked()) {
            return [
                Placeholder::make('linked_scheduling_locked_moto')
                    ->hiddenLabel()
                    ->content(new HtmlString(
                        '<p class="text-sm text-gray-600 dark:text-gray-400">Онлайн-запись недоступна на текущем тарифе. Обратитесь к администратору платформы, чтобы включить модуль записи.</p>'
                    )),
            ];
        }

        return [
            self::motorcycleSummarySection(),
            Section::make('Онлайн-запись')
                ->description(FilamentInlineMarkdown::toHtml(
                    'Запись на **модель** (каталог). Создание и привязка услуги — только из этой карточки. В разделе «Услуги (запись)» linked-записи отображаются для обзора.'
                ))
                ->schema([
                    ...self::sharedLinkedFields(
                        enableLabel: 'Включить онлайн-запись на эту модель',
                        syncTitleLabel: 'Синхронизировать название услуги с названием модели',
                        targetLinkName: 'linked_target_link_moto',
                        targetLinkContent: fn (?Motorcycle $record): HtmlString => self::targetLinkContentForMotorcycle($record),
                    ),
                ])
                ->visibleOn('edit')
                ->columnSpan(['default' => 12, 'lg' => 12]),
        ];
    }

    /**
     * Схема для изолированного Livewire-редактора «Онлайн-запись» на edit мотоцикла.
     *
     * @return list<Section|Placeholder|Component>
     */
    public static function motorcycleOnlineBookingEditorSchema(): array
    {
        return self::motorcycleOnlineBookingTabSchema();
    }

    /**
     * @return list<Component>
     */
    private static function rentalUnitOnlineBookingTabSchema(): array
    {
        if (self::schedulingLocked()) {
            return [
                Placeholder::make('linked_scheduling_locked_unit')
                    ->hiddenLabel()
                    ->content(new HtmlString(
                        '<p class="text-sm text-gray-600 dark:text-gray-400">Онлайн-запись недоступна на текущем тарифе. Обратитесь к администратору платформы, чтобы включить модуль записи.</p>'
                    )),
            ];
        }

        return [
            self::rentalUnitSummarySection(),
            Section::make('Онлайн-запись')
                ->description(FilamentInlineMarkdown::toHtml(
                    'Запись на **конкретную единицу парка**. Создание и привязка услуги — только из этой карточки.'
                ))
                ->schema([
                    ...self::sharedLinkedFields(
                        enableLabel: 'Включить онлайн-запись на эту единицу',
                        syncTitleLabel: 'Синхронизировать название услуги с подписью единицы парка',
                        targetLinkName: 'linked_target_link_unit',
                        targetLinkContent: fn (?RentalUnit $record): HtmlString => self::targetLinkContentForRentalUnit($record),
                    ),
                ])
                ->visibleOn('edit')
                ->columnSpan(['default' => 12, 'lg' => 12]),
        ];
    }

    /**
     * @param  Closure(?Motorcycle): HtmlString|Closure(?RentalUnit): HtmlString  $targetLinkContent
     * @return list<Component>
     */
    private static function sharedLinkedFields(
        string $enableLabel,
        string $syncTitleLabel,
        string $targetLinkName,
        Closure $targetLinkContent,
    ): array {
        return [
            Toggle::make('linked_booking_enabled')
                ->label($enableLabel)
                ->default(false)
                ->live(debounce: 400),
            Toggle::make('linked_sync_title_from_source')
                ->label($syncTitleLabel)
                ->default(true)
                ->helperText('Если выключено, название услуги не меняется при переименовании источника.'),
            TextInput::make('linked_duration_minutes')
                ->label('Длительность слота')
                ->suffix('мин')
                ->numeric()
                ->minValue(1)
                ->default(60)
                ->required(),
            TextInput::make('linked_slot_step_minutes')
                ->label('Шаг между слотами')
                ->suffix('мин')
                ->numeric()
                ->minValue(5)
                ->default(15)
                ->required(),
            TextInput::make('linked_buffer_before_minutes')
                ->label('Буфер до')
                ->suffix('мин')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->required(),
            TextInput::make('linked_buffer_after_minutes')
                ->label('Буфер после')
                ->suffix('мин')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->required(),
            TextInput::make('linked_min_booking_notice_minutes')
                ->label('Мин. уведомление до слота')
                ->suffix('мин')
                ->numeric()
                ->minValue(0)
                ->default(120)
                ->required(),
            TextInput::make('linked_max_booking_horizon_days')
                ->label('Запись не дальше (дней)')
                ->numeric()
                ->minValue(1)
                ->default(60)
                ->required(),
            Toggle::make('linked_requires_confirmation')
                ->label('Подтверждать заявку вручную')
                ->default(true),
            TextInput::make('linked_sort_weight')
                ->label('Порядок в списке услуг')
                ->numeric()
                ->default(0),
            Placeholder::make($targetLinkName)
                ->label('Цель расписания')
                ->content(function (?Model $record) use ($targetLinkContent): HtmlString {
                    if ($record instanceof Motorcycle || $record instanceof RentalUnit) {
                        return $targetLinkContent($record);
                    }

                    return new HtmlString('');
                }),
        ];
    }

    private static function targetLinkContentForMotorcycle(?Motorcycle $record): HtmlString
    {
        if ($record === null || ! $record->exists) {
            return new HtmlString('');
        }
        $svc = app(LinkedBookableServiceManager::class)->findLinkedForMotorcycle($record, SchedulingScope::Tenant);
        if ($svc === null) {
            return new HtmlString('<span class="text-sm text-gray-500">Появится после включения записи и сохранения.</span>');
        }
        $target = $svc->schedulingTarget;
        if ($target === null) {
            return new HtmlString('<span class="text-sm text-gray-500">Цель создаётся автоматически.</span>');
        }
        $url = SchedulingTargetResource::getUrl('edit', ['record' => $target->id]);

        return new HtmlString(
            '<a href="'.e($url).'" class="text-primary-600 text-sm font-medium underline dark:text-primary-400">Открыть цель расписания</a>'
        );
    }

    private static function targetLinkContentForRentalUnit(?RentalUnit $record): HtmlString
    {
        if ($record === null || ! $record->exists) {
            return new HtmlString('');
        }
        $svc = app(LinkedBookableServiceManager::class)->findLinkedForRentalUnit($record, SchedulingScope::Tenant);
        if ($svc === null) {
            return new HtmlString('<span class="text-sm text-gray-500">Появится после включения записи и сохранения.</span>');
        }
        $target = $svc->schedulingTarget;
        if ($target === null) {
            return new HtmlString('<span class="text-sm text-gray-500">Цель создаётся автоматически.</span>');
        }
        $url = SchedulingTargetResource::getUrl('edit', ['record' => $target->id]);

        return new HtmlString(
            '<a href="'.e($url).'" class="text-primary-600 text-sm font-medium underline dark:text-primary-400">Открыть цель расписания</a>'
        );
    }

    /**
     * @return list<string>
     */
    public static function linkedFieldNames(): array
    {
        return [
            'linked_booking_enabled',
            'linked_sync_title_from_source',
            'linked_duration_minutes',
            'linked_slot_step_minutes',
            'linked_buffer_before_minutes',
            'linked_buffer_after_minutes',
            'linked_min_booking_notice_minutes',
            'linked_max_booking_horizon_days',
            'linked_requires_confirmation',
            'linked_sort_weight',
        ];
    }

    public static function validationExceptionAffectsLinkedFields(ValidationException $e): bool
    {
        foreach (array_keys($e->errors()) as $key) {
            foreach (self::linkedFieldNames() as $field) {
                if ($key === $field
                    || str_ends_with($key, '.'.$field)
                    || str_contains($key, '.'.$field.'.')
                    || str_contains($key, 'data.'.$field)
                ) {
                    return true;
                }
            }
        }

        return false;
    }
}
