<?php

namespace App\Jobs;

use App\Models\NotificationDelivery;
use App\Models\NotificationDeliveryAttempt;
use App\Models\Tenant;
use App\NotificationCenter\NotificationChannelDriverFactory;
use App\NotificationCenter\NotificationDeliveryStatus;
use App\NotificationCenter\UnsupportedNotificationChannelException;
use App\Services\CurrentTenantManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Carbon;
use Throwable;

class DispatchNotificationDeliveryJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 5;

    public function __construct(
        public int $deliveryId,
    ) {}

    public function backoff(): array
    {
        return [10, 30, 120, 300];
    }

    public function handle(
        CurrentTenantManager $tenantManager,
        NotificationChannelDriverFactory $drivers,
    ): void {
        $delivery = NotificationDelivery::query()
            ->with(['event', 'destination', 'tenant'])
            ->find($this->deliveryId);

        if ($delivery === null) {
            return;
        }

        if (in_array($delivery->status, [
            NotificationDeliveryStatus::Delivered->value,
            NotificationDeliveryStatus::Skipped->value,
            NotificationDeliveryStatus::Cancelled->value,
        ], true)) {
            return;
        }

        $jobAttempt = $this->job ? $this->job->attempts() : 1;
        if ($delivery->status === NotificationDeliveryStatus::Failed->value && $jobAttempt > 1) {
            NotificationDelivery::query()
                ->whereKey($this->deliveryId)
                ->where('status', NotificationDeliveryStatus::Failed->value)
                ->update([
                    'status' => NotificationDeliveryStatus::Queued->value,
                    'failed_at' => null,
                    'error_message' => null,
                ]);
            $delivery->refresh();
        }

        $tenant = $delivery->tenant;
        if (! $tenant instanceof Tenant) {
            return;
        }

        if ($delivery->status !== NotificationDeliveryStatus::Queued->value) {
            return;
        }

        $claimed = NotificationDelivery::query()
            ->whereKey($this->deliveryId)
            ->where('status', NotificationDeliveryStatus::Queued->value)
            ->update(['status' => NotificationDeliveryStatus::Processing->value]);

        if ($claimed === 0) {
            return;
        }

        $delivery->refresh();

        try {
            $tenantManager->setTenant($tenant);

            $attemptNo = (int) NotificationDeliveryAttempt::query()
                ->where('delivery_id', $delivery->id)
                ->max('attempt_no') + 1;

            $attempt = NotificationDeliveryAttempt::query()->create([
                'delivery_id' => $delivery->id,
                'attempt_no' => $attemptNo,
                'status' => 'running',
                'started_at' => Carbon::now(),
            ]);

            try {
                $event = $delivery->event;
                $destination = $delivery->destination;
                if ($event === null || $destination === null) {
                    throw new \RuntimeException('Missing event or destination');
                }

                $driver = $drivers->forType($delivery->channel_type);
                $driver->send($delivery, $event, $destination);

                $delivery->refresh();

                $attempt->update([
                    'status' => 'succeeded',
                    'finished_at' => Carbon::now(),
                    'response_json' => $this->attemptResponseSnapshot($delivery),
                ]);
            } catch (UnsupportedNotificationChannelException $e) {
                $attempt->update([
                    'status' => 'skipped',
                    'finished_at' => Carbon::now(),
                    'error_message' => $e->getMessage(),
                    'response_json' => [
                        'skipped' => true,
                        'reason' => $e->getMessage(),
                    ],
                ]);
                $delivery->update([
                    'status' => NotificationDeliveryStatus::Skipped->value,
                    'error_message' => $e->getMessage(),
                ]);
            } catch (Throwable $e) {
                $attempt->update([
                    'status' => 'failed',
                    'finished_at' => Carbon::now(),
                    'error_message' => $e->getMessage(),
                    'response_json' => [
                        'error' => true,
                        'message' => $e->getMessage(),
                    ],
                ]);

                $isLast = $jobAttempt >= $this->tries;
                $delivery->update([
                    'status' => NotificationDeliveryStatus::Failed->value,
                    'failed_at' => Carbon::now(),
                    'error_message' => $e->getMessage(),
                ]);

                if (! $isLast) {
                    throw $e;
                }
            }
        } finally {
            $tenantManager->clear();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function attemptResponseSnapshot(NotificationDelivery $delivery): array
    {
        $fromDelivery = $delivery->response_json;
        if (is_array($fromDelivery) && $fromDelivery !== []) {
            return $fromDelivery;
        }

        return [
            'ok' => true,
            'delivery_status' => $delivery->status,
            'provider_message_id' => $delivery->provider_message_id,
        ];
    }
}
