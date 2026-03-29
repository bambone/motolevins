<?php

namespace App\Jobs\Mail;

use App\Jobs\Mail\Middleware\TenantMailRateLimited;
use App\Models\TenantMailLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class SendTenantMailableJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 50;

    /**
     * @param  list<string>  $to
     */
    public function __construct(
        public int $tenantId,
        public ?int $mailLogId,
        public Mailable $mailable,
        public array $to,
        public int $mailRateLimitPerMinute,
        public string $correlationId,
    ) {}

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            new TenantMailRateLimited('tenant-mails'),
        ];
    }

    public function handle(): void
    {
        $log = $this->mailLogId !== null
            ? TenantMailLog::query()->find($this->mailLogId)
            : null;

        if ($log !== null) {
            $log->update([
                'status' => TenantMailLog::STATUS_PROCESSING,
                'started_at' => $log->started_at ?? now(),
                'attempts' => $this->attempts(),
            ]);
        }

        try {
            Mail::to($this->to)->send($this->mailable);

            if ($log !== null) {
                $log->update([
                    'status' => TenantMailLog::STATUS_SENT,
                    'sent_at' => now(),
                    'error_message' => null,
                ]);
            }

            Log::info('tenant_mail.sent', [
                'tenant_id' => $this->tenantId,
                'correlation_id' => $this->correlationId,
                'mail_log_id' => $this->mailLogId,
                'mailable_class' => $this->mailable::class,
            ]);
        } catch (Throwable $e) {
            if ($log !== null) {
                $log->update([
                    'status' => TenantMailLog::STATUS_FAILED,
                    'failed_at' => now(),
                    'error_message' => Str::limit($e->getMessage(), 500),
                    'attempts' => $this->attempts(),
                ]);
            }

            Log::error('tenant_mail.send_failed', [
                'tenant_id' => $this->tenantId,
                'correlation_id' => $this->correlationId,
                'mail_log_id' => $this->mailLogId,
                'mailable_class' => $this->mailable::class,
                'message' => Str::limit($e->getMessage(), 500),
            ]);

            throw $e;
        }
    }

    public function failed(?Throwable $exception): void
    {
        if ($this->mailLogId === null) {
            return;
        }

        TenantMailLog::query()->whereKey($this->mailLogId)->update([
            'status' => TenantMailLog::STATUS_FAILED,
            'failed_at' => now(),
            'error_message' => $exception !== null
                ? Str::limit($exception->getMessage(), 500)
                : 'Unknown failure',
            'attempts' => $this->attempts(),
        ]);
    }
}
