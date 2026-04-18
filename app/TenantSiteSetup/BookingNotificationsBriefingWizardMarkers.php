<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

use App\Models\BookingSettingsPreset;
use App\Models\NotificationDestination;
use App\Models\NotificationSubscription;
use App\Models\Tenant;

/**
 * Маркеры сущностей, созданных мастером «Запись и уведомления (бриф)».
 * Используются для completion/snapshot (актуальное состояние), а не только факта apply.
 */
final class BookingNotificationsBriefingWizardMarkers
{
    public const PRESET_NAME = 'Быстрый старт (мастер запуска)';

    public const DEST_EMAIL_NAME = 'Email (мастер запуска)';

    public const DEST_TELEGRAM_NAME = 'Telegram (мастер запуска)';

    /** Суффикс в названии правил, создаваемых мастером. */
    public const SUBSCRIPTION_NAME_MARKER = 'мастер запуска';

    /**
     * Есть ли в кабинете хотя бы одна сущность, созданная/поддерживаемая этим мастером.
     */
    public static function hasAnyWizardArtifact(Tenant $tenant): bool
    {
        $tid = (int) $tenant->id;

        if (BookingSettingsPreset::query()->where('tenant_id', $tid)->where('name', self::PRESET_NAME)->exists()) {
            return true;
        }

        if (NotificationDestination::query()
            ->where('tenant_id', $tid)
            ->whereIn('name', [self::DEST_EMAIL_NAME, self::DEST_TELEGRAM_NAME])
            ->exists()) {
            return true;
        }

        return NotificationSubscription::query()
            ->where('tenant_id', $tid)
            ->where('name', 'like', '%'.self::SUBSCRIPTION_NAME_MARKER.'%')
            ->exists();
    }

    /**
     * Краткая строка для snapshot (без опоры только на applied_at).
     */
    public static function snapshotLine(Tenant $tenant): string
    {
        if (! self::hasAnyWizardArtifact($tenant)) {
            return 'нет данных мастера';
        }

        $tid = (int) $tenant->id;
        $parts = [];
        if (BookingSettingsPreset::query()->where('tenant_id', $tid)->where('name', self::PRESET_NAME)->exists()) {
            $parts[] = 'пресет';
        }
        if (NotificationDestination::query()->where('tenant_id', $tid)->where('name', self::DEST_EMAIL_NAME)->exists()) {
            $parts[] = 'email';
        }
        if (NotificationDestination::query()->where('tenant_id', $tid)->where('name', self::DEST_TELEGRAM_NAME)->exists()) {
            $parts[] = 'telegram';
        }
        $ruleCount = NotificationSubscription::query()
            ->where('tenant_id', $tid)
            ->where('name', 'like', '%'.self::SUBSCRIPTION_NAME_MARKER.'%')
            ->count();
        if ($ruleCount > 0) {
            $parts[] = 'правил: '.$ruleCount;
        }

        return $parts === [] ? 'настроено' : 'настроено: '.implode(', ', $parts);
    }
}
