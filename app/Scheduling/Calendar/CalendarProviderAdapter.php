<?php

declare(strict_types=1);

namespace App\Scheduling\Calendar;

use App\Models\CalendarConnection;
use Carbon\Carbon;

/**
 * Provider-specific calendar integration (Google API, CalDAV family).
 */
interface CalendarProviderAdapter
{
    public function supports(CalendarConnection $connection): bool;

    /**
     * @return list<array{id: string, title: string}>
     */
    public function listCalendars(CalendarConnection $connection): array;

    /**
     * Fetches busy ranges into external_busy_blocks (implementation writes DB or returns DTOs).
     */
    public function syncBusy(CalendarConnection $connection, Carbon $rangeStartUtc, Carbon $rangeEndUtc): void;

    public function supportsWebhooks(): bool;
}
