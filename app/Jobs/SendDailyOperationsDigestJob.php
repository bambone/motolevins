<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\NotificationCenter\NotificationEventRecorder;
use App\NotificationCenter\Presenters\DigestOperationsPresenter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

class SendDailyOperationsDigestJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ?Carbon $forDay = null,
    ) {}

    public function handle(
        NotificationEventRecorder $recorder,
        DigestOperationsPresenter $presenter,
    ): void {
        $day = $this->forDay ?? Carbon::yesterday();

        Tenant::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->chunkById(50, function ($tenants) use ($recorder, $presenter, $day): void {
                foreach ($tenants as $tenant) {
                    $payload = $presenter->dailyPayloadForTenant($tenant, $day);
                    $dedupe = 'digest:daily:'.$tenant->id.':'.$day->toDateString();
                    $recorder->record(
                        (int) $tenant->id,
                        'digest.daily_operations',
                        'TenantDigest',
                        (int) $tenant->id,
                        $payload,
                        dedupeKey: $dedupe,
                    );
                }
            });
    }
}
