<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

use App\Models\TenantSetting;

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
     * @return array<string, mixed>
     */
    public function getMerged(int $tenantId): array
    {
        $raw = TenantSetting::getForTenant($tenantId, self::SETTING_KEY, []);

        return array_merge($this->defaults(), is_array($raw) ? $raw : []);
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
