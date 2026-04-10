<?php

declare(strict_types=1);

namespace App\Jobs\Scheduling;

use App\Models\CalendarConnection;
use App\Models\ExternalBusyBlock;
use App\Scheduling\Calendar\CalendarAdapterRegistry;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Заполняет {@see ExternalBusyBlock} через провайдерский адаптер.
 */
final class SyncCalendarBusyJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $calendarConnectionId,
        public string $rangeStartUtcIso,
        public string $rangeEndUtcIso,
    ) {}

    public function handle(CalendarAdapterRegistry $registry): void
    {
        $connection = CalendarConnection::query()->find($this->calendarConnectionId);
        if ($connection === null || ! $connection->is_active) {
            return;
        }

        $adapter = $registry->forConnection($connection);
        $adapter->syncBusy(
            $connection,
            Carbon::parse($this->rangeStartUtcIso, 'UTC'),
            Carbon::parse($this->rangeEndUtcIso, 'UTC'),
        );
    }
}
