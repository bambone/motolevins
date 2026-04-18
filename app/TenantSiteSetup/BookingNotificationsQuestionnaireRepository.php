<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

use App\Models\BookingSettingsPreset;
use App\Models\NotificationDestination;
use App\Models\NotificationSubscription;
use App\Models\Tenant;
use App\Models\TenantSetting;
use App\NotificationCenter\NotificationChannelType;
use App\NotificationCenter\NotificationEventRegistry;
use App\Scheduling\SchedulingTimezoneOptions;

/**
 * Черновик и результат анкеты «Запись и уведомления» (v1) для мастера запуска.
 *
 * @phpstan-type QuestionnaireData array{
 *   schema_version?: int,
 *   meta_brand_name?: string,
 *   meta_timezone?: string,
 *   sched_duration_min?: int|null,
 *   sched_slot_step_min?: int|null,
 *   sched_buffer_before?: int|null,
 *   sched_buffer_after?: int|null,
 *   sched_horizon_days?: int|null,
 *   sched_notice_min?: int|null,
 *   sched_requires_confirmation?: bool|null,
 *   dest_email?: string,
 *   dest_telegram_chat_id?: string,
 *   events_enabled?: list<string>,
 *   applied_at?: string|null,
 * }
 */
final class BookingNotificationsQuestionnaireRepository
{
    public const SETTING_KEY = 'setup.booking_notifications_questionnaire';

    public const APPLIED_AT_KEY = 'setup.booking_notifications_applied_at';

    public function schemaVersion(): int
    {
        return 1;
    }

    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return [
            'schema_version' => $this->schemaVersion(),
            'meta_brand_name' => '',
            'meta_timezone' => '',
            'sched_duration_min' => 60,
            'sched_slot_step_min' => 15,
            'sched_buffer_before' => 0,
            'sched_buffer_after' => 0,
            'sched_horizon_days' => 60,
            'sched_notice_min' => 120,
            'sched_requires_confirmation' => true,
            'dest_email' => '',
            'dest_telegram_chat_id' => '',
            'events_enabled' => [
                'crm_request.created',
                'booking.created',
            ],
        ];
    }

    /**
     * Пустые поля черновика дополняются из БД: бренд (как у {@see resolvedPublicSiteName()}),
     * часовой пояс тенанта / app.timezone, email и Telegram из получателей (сначала созданные мастером),
     * параметры записи из пресета (пресет мастера или первый пресет тенанта, только если числа ещё «как в defaults()`),
     * список событий: если в черновике ещё дефолтный набор — подставить события из правил мастера (если есть); иначе оставить сохранённый список; затем всегда {@see filterEventKeysForTenant()} (без booking.* при выключенном модуле).
     *
     * @return array<string, mixed>
     */
    public function getMerged(int $tenantId): array
    {
        $raw = TenantSetting::getForTenant($tenantId, self::SETTING_KEY, []);

        $merged = array_merge($this->defaults(), is_array($raw) ? $raw : []);

        $tenant = Tenant::query()->find($tenantId);

        if ($this->shouldPrefillMetaBrandNameFromDatabase($merged)) {
            $fallback = $this->resolvedPublicSiteName($tenantId);
            if ($fallback !== '') {
                $merged['meta_brand_name'] = $fallback;
            }
        }

        if ($this->shouldPrefillMetaTimezoneFromDatabase($merged)) {
            $tz = $this->resolvedTimezone($tenant);
            if ($tz !== '') {
                $merged['meta_timezone'] = $tz;
            }
        }

        if ($this->shouldPrefillDestEmailFromDatabase($merged)) {
            $email = $this->resolvedEmailFromDestinations($tenantId);
            if ($email !== '') {
                $merged['dest_email'] = $email;
            }
        }

        if ($this->shouldPrefillDestTelegramFromDatabase($merged)) {
            $tg = $this->resolvedTelegramFromDestinations($tenantId);
            if ($tg !== '') {
                $merged['dest_telegram_chat_id'] = $tg;
            }
        }

        if ($this->schedFieldsMatchDefaults($merged)) {
            $sched = $this->schedFieldsFromBookingPreset($tenantId);
            if ($sched !== null) {
                $merged = array_merge($merged, $sched);
            }
        }

        if ($tenant !== null) {
            $wizardKeys = $this->eventKeysFromWizardSubscriptions($tenantId);
            $stillDefaultEvents = $this->eventsListMatchesDefaults($merged);
            if ($wizardKeys !== [] && $stillDefaultEvents) {
                $source = $wizardKeys;
            } else {
                $source = array_values(array_map('strval', (array) ($merged['events_enabled'] ?? [])));
            }
            $merged['events_enabled'] = $this->filterEventKeysForTenant($tenant, $source);
        }

        $merged['meta_timezone'] = SchedulingTimezoneOptions::normalizeToKnown((string) ($merged['meta_timezone'] ?? ''));

        return $merged;
    }

    /**
     * Пока в черновике анкеты нет своего значения — подставляем публичное имя сайта из настроек / тенанта.
     *
     * @param  array<string, mixed>  $merged
     */
    private function shouldPrefillMetaBrandNameFromDatabase(array $merged): bool
    {
        return trim((string) ($merged['meta_brand_name'] ?? '')) === '';
    }

    private function resolvedPublicSiteName(int $tenantId): string
    {
        $fromSettings = trim((string) TenantSetting::getForTenant($tenantId, 'general.site_name', ''));
        if ($fromSettings !== '') {
            return $fromSettings;
        }

        $tenant = Tenant::query()->find($tenantId);

        return $tenant !== null ? trim($tenant->defaultPublicSiteName()) : '';
    }

    /**
     * @param  array<string, mixed>  $merged
     */
    private function shouldPrefillMetaTimezoneFromDatabase(array $merged): bool
    {
        return trim((string) ($merged['meta_timezone'] ?? '')) === '';
    }

    private function resolvedTimezone(?Tenant $tenant): string
    {
        if ($tenant !== null) {
            $tz = trim((string) ($tenant->timezone ?? ''));
            if ($tz !== '') {
                return $tz;
            }
        }

        return trim((string) config('app.timezone', 'UTC'));
    }

    /**
     * @param  array<string, mixed>  $merged
     */
    private function shouldPrefillDestEmailFromDatabase(array $merged): bool
    {
        return trim((string) ($merged['dest_email'] ?? '')) === '';
    }

    /**
     * @param  array<string, mixed>  $merged
     */
    private function shouldPrefillDestTelegramFromDatabase(array $merged): bool
    {
        return trim((string) ($merged['dest_telegram_chat_id'] ?? '')) === '';
    }

    private function resolvedEmailFromDestinations(int $tenantId): string
    {
        $wizard = NotificationDestination::query()
            ->where('tenant_id', $tenantId)
            ->where('type', NotificationChannelType::Email->value)
            ->where('name', BookingNotificationsBriefingWizardMarkers::DEST_EMAIL_NAME)
            ->first();
        if ($wizard !== null) {
            $email = trim((string) ($wizard->config_json['email'] ?? ''));
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $email;
            }
        }

        $candidates = NotificationDestination::query()
            ->where('tenant_id', $tenantId)
            ->where('type', NotificationChannelType::Email->value)
            ->orderBy('id')
            ->get();

        foreach ($candidates as $dest) {
            $email = trim((string) ($dest->config_json['email'] ?? ''));
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $email;
            }
        }

        return '';
    }

    private function resolvedTelegramFromDestinations(int $tenantId): string
    {
        $wizard = NotificationDestination::query()
            ->where('tenant_id', $tenantId)
            ->where('type', NotificationChannelType::Telegram->value)
            ->where('name', BookingNotificationsBriefingWizardMarkers::DEST_TELEGRAM_NAME)
            ->first();
        if ($wizard !== null) {
            $chatId = trim((string) ($wizard->config_json['chat_id'] ?? ''));
            if ($chatId !== '') {
                return $chatId;
            }
        }

        $candidates = NotificationDestination::query()
            ->where('tenant_id', $tenantId)
            ->where('type', NotificationChannelType::Telegram->value)
            ->orderBy('id')
            ->get();

        foreach ($candidates as $dest) {
            $chatId = trim((string) ($dest->config_json['chat_id'] ?? ''));
            if ($chatId !== '') {
                return $chatId;
            }
        }

        return '';
    }

    /**
     * Анкета ещё на «заводских» числах пресета — можно подтянуть живой пресет из БД.
     *
     * @param  array<string, mixed>  $merged
     */
    private function schedFieldsMatchDefaults(array $merged): bool
    {
        $d = $this->defaults();

        return (int) ($merged['sched_duration_min'] ?? 0) === (int) $d['sched_duration_min']
            && (int) ($merged['sched_slot_step_min'] ?? 0) === (int) $d['sched_slot_step_min']
            && (int) ($merged['sched_buffer_before'] ?? 0) === (int) $d['sched_buffer_before']
            && (int) ($merged['sched_buffer_after'] ?? 0) === (int) $d['sched_buffer_after']
            && (int) ($merged['sched_horizon_days'] ?? 0) === (int) $d['sched_horizon_days']
            && (int) ($merged['sched_notice_min'] ?? 0) === (int) $d['sched_notice_min']
            && (bool) ($merged['sched_requires_confirmation'] ?? false) === (bool) $d['sched_requires_confirmation'];
    }

    /**
     * Сначала пресет мастера, иначе любой существующий пресет тенанта.
     *
     * @return array<string, mixed>|null
     */
    private function schedFieldsFromBookingPreset(int $tenantId): ?array
    {
        $preset = BookingSettingsPreset::query()
            ->where('tenant_id', $tenantId)
            ->where('name', BookingNotificationsBriefingWizardMarkers::PRESET_NAME)
            ->first();

        // Fallback: первый пресет по id (может быть нерелевантен старым данным — осознанный компромисс prefill).
        if ($preset === null) {
            $preset = BookingSettingsPreset::query()
                ->where('tenant_id', $tenantId)
                ->orderBy('id')
                ->first();
        }

        if ($preset === null) {
            return null;
        }

        $payload = is_array($preset->payload) ? $preset->payload : [];
        $d = $this->defaults();

        return [
            'sched_duration_min' => (int) ($payload['duration_minutes'] ?? $d['sched_duration_min']),
            'sched_slot_step_min' => (int) ($payload['slot_step_minutes'] ?? $d['sched_slot_step_min']),
            'sched_buffer_before' => (int) ($payload['buffer_before_minutes'] ?? $d['sched_buffer_before']),
            'sched_buffer_after' => (int) ($payload['buffer_after_minutes'] ?? $d['sched_buffer_after']),
            'sched_notice_min' => (int) ($payload['min_booking_notice_minutes'] ?? $d['sched_notice_min']),
            'sched_horizon_days' => (int) ($payload['max_booking_horizon_days'] ?? $d['sched_horizon_days']),
            'sched_requires_confirmation' => (bool) ($payload['requires_confirmation'] ?? $d['sched_requires_confirmation']),
        ];
    }

    /**
     * Совпадает ли список событий в merged с дефолтом анкеты (порядок не важен).
     *
     * @param  array<string, mixed>  $merged
     */
    private function eventsListMatchesDefaults(array $merged): bool
    {
        $d = $this->defaults()['events_enabled'];
        $cur = $merged['events_enabled'] ?? [];
        if (! is_array($cur)) {
            return false;
        }

        $a = array_map('strval', $d);
        $b = array_map('strval', $cur);
        sort($a);
        sort($b);

        return $a === $b;
    }

    /**
     * @return list<string>
     */
    private function eventKeysFromWizardSubscriptions(int $tenantId): array
    {
        $marker = BookingNotificationsBriefingWizardMarkers::SUBSCRIPTION_NAME_MARKER;

        $keys = NotificationSubscription::query()
            ->where('tenant_id', $tenantId)
            ->where('name', 'like', '%'.$marker.'%')
            ->pluck('event_key')
            ->map(fn ($k): string => (string) $k)
            ->filter()
            ->unique()
            ->values()
            ->all();

        sort($keys);

        return array_values(array_filter($keys, fn (string $k): bool => NotificationEventRegistry::has($k)));
    }

    /**
     * @param  list<string>  $keys
     * @return list<string>
     */
    private function filterEventKeysForTenant(Tenant $tenant, array $keys): array
    {
        $schedulingOn = (bool) $tenant->scheduling_module_enabled;

        $out = [];
        foreach ($keys as $key) {
            if (! NotificationEventRegistry::has($key)) {
                continue;
            }
            if (! $schedulingOn && str_starts_with($key, 'booking.')) {
                continue;
            }
            $out[] = $key;
        }

        return array_values(array_unique($out));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(int $tenantId, array $data): void
    {
        $data['schema_version'] = $this->schemaVersion();
        TenantSetting::setForTenant($tenantId, self::SETTING_KEY, $data, 'json');
    }

    public function appliedAt(int $tenantId): ?string
    {
        $v = TenantSetting::getForTenant($tenantId, self::APPLIED_AT_KEY, '');

        return is_string($v) && $v !== '' ? $v : null;
    }

    public function markApplied(int $tenantId): void
    {
        TenantSetting::setForTenant($tenantId, self::APPLIED_AT_KEY, now()->toIso8601String(), 'string');
    }
}
