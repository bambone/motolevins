<?php

declare(strict_types=1);

namespace App\Scheduling\Calendar;

use App\Models\CalendarConnection;
use App\Scheduling\Enums\CalendarProviderType;
use Carbon\Carbon;

/**
 * Yandex / Mail.ru CalDAV — HTTP client + sync pipeline to be implemented.
 */
final class CaldavCalendarProviderAdapter implements CalendarProviderAdapter
{
    public function supports(CalendarConnection $connection): bool
    {
        return in_array($connection->provider, [CalendarProviderType::Yandex, CalendarProviderType::Mailru], true);
    }

    public function listCalendars(CalendarConnection $connection): array
    {
        return [];
    }

    public function syncBusy(CalendarConnection $connection, Carbon $rangeStartUtc, Carbon $rangeEndUtc): void
    {
        // REPORT calendar-query / sync-token → external_busy_blocks.
    }

    public function supportsWebhooks(): bool
    {
        return false;
    }
}
