<?php

declare(strict_types=1);

namespace App\Scheduling\Calendar;

use App\Models\CalendarConnection;
use Carbon\Carbon;

/**
 * Fallback when no real adapter is registered (MVP stub).
 */
final class NullCalendarProviderAdapter implements CalendarProviderAdapter
{
    public function supports(CalendarConnection $connection): bool
    {
        return false;
    }

    public function listCalendars(CalendarConnection $connection): array
    {
        return [];
    }

    public function syncBusy(CalendarConnection $connection, Carbon $rangeStartUtc, Carbon $rangeEndUtc): void
    {
        // No-op until Google/CalDAV credentials and HTTP client are wired.
    }

    public function supportsWebhooks(): bool
    {
        return false;
    }
}
