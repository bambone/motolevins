<?php

namespace App\Jobs;

use App\Models\CrmRequest;
use App\Models\Tenant;
use App\NotificationCenter\NotificationEventRecorder;
use App\NotificationCenter\Presenters\CrmRequestNotificationPresenter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

class ScanCrmRequestSlaNotificationsJob implements ShouldQueue
{
    use Queueable;

    public function handle(
        NotificationEventRecorder $recorder,
        CrmRequestNotificationPresenter $presenter,
    ): void {
        $now = Carbon::now();

        $this->emitUnviewed5m($now, $recorder, $presenter);
        $this->emitUnprocessed15m($now, $recorder, $presenter);
    }

    private function emitUnviewed5m(
        Carbon $now,
        NotificationEventRecorder $recorder,
        CrmRequestNotificationPresenter $presenter,
    ): void {
        $cutoff = $now->copy()->subMinutes(5);

        CrmRequest::query()
            ->whereNull('first_viewed_at')
            ->where('status', CrmRequest::STATUS_NEW)
            ->where('created_at', '<=', $cutoff)
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($recorder, $presenter): void {
                foreach ($rows as $crm) {
                    $tenant = Tenant::query()->find($crm->tenant_id);
                    if ($tenant === null) {
                        continue;
                    }

                    $payload = $presenter->payloadForUnviewed5m($tenant, $crm);
                    $recorder->record(
                        (int) $crm->tenant_id,
                        'crm_request.unviewed_5m',
                        class_basename(CrmRequest::class),
                        (int) $crm->id,
                        $payload,
                        dedupeKey: 'crm:'.(int) $crm->id.':unviewed:5m',
                    );
                }
            });
    }

    private function emitUnprocessed15m(
        Carbon $now,
        NotificationEventRecorder $recorder,
        CrmRequestNotificationPresenter $presenter,
    ): void {
        $cutoff = $now->copy()->subMinutes(15);

        CrmRequest::query()
            ->whereNull('processed_at')
            ->where('status', CrmRequest::STATUS_NEW)
            ->where('created_at', '<=', $cutoff)
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($recorder, $presenter): void {
                foreach ($rows as $crm) {
                    $tenant = Tenant::query()->find($crm->tenant_id);
                    if ($tenant === null) {
                        continue;
                    }

                    $payload = $presenter->payloadForUnprocessed15m($tenant, $crm);
                    $recorder->record(
                        (int) $crm->tenant_id,
                        'crm_request.unprocessed_15m',
                        class_basename(CrmRequest::class),
                        (int) $crm->id,
                        $payload,
                        dedupeKey: 'crm:'.(int) $crm->id.':unprocessed:15m',
                    );
                }
            });
    }
}
