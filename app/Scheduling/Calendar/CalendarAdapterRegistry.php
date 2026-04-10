<?php

declare(strict_types=1);

namespace App\Scheduling\Calendar;

use App\Models\CalendarConnection;

final class CalendarAdapterRegistry
{
    /**
     * @param  list<CalendarProviderAdapter>  $adapters
     */
    public function __construct(
        private readonly array $adapters,
        private readonly NullCalendarProviderAdapter $fallback,
    ) {}

    public function forConnection(CalendarConnection $connection): CalendarProviderAdapter
    {
        foreach ($this->adapters as $adapter) {
            if ($adapter->supports($connection)) {
                return $adapter;
            }
        }

        return $this->fallback;
    }
}
