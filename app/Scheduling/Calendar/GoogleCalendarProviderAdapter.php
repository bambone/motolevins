<?php

declare(strict_types=1);

namespace App\Scheduling\Calendar;

use App\Models\CalendarConnection;
use App\Scheduling\Enums\CalendarProviderType;
use Carbon\Carbon;

/**
 * Google Calendar: OAuth + freeBusy/events — wire HTTP in a follow-up; structure matches {@see CalendarProviderAdapter}.
 */
final class GoogleCalendarProviderAdapter implements CalendarProviderAdapter
{
    public function supports(CalendarConnection $connection): bool
    {
        return $connection->provider === CalendarProviderType::Google;
    }

    public function listCalendars(CalendarConnection $connection): array
    {
        return [];
    }

    public function syncBusy(CalendarConnection $connection, Carbon $rangeStartUtc, Carbon $rangeEndUtc): void
    {
        // Populate via Google Calendar API (freeBusy / events list) → external_busy_blocks.
    }

    public function supportsWebhooks(): bool
    {
        return true;
    }
}
