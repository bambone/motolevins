<?php

namespace App\Services\Mail;

use App\Contracts\Mail\DefinesTenantMailMetadata;
use App\Jobs\Mail\SendTenantMailableJob;
use App\Models\Tenant;
use App\Models\TenantMailLog;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final class TenantMailQueueBuilder
{
    /**
     * @param  list<string>  $to
     */
    public function __construct(
        private readonly TenantMailer $mailer,
        private readonly Tenant $tenant,
        private readonly array $to,
    ) {}

    public function queue(Mailable $mailable): void
    {
        if ($this->to === []) {
            throw new \LogicException('Tenant mail requires at least one recipient.');
        }

        $toPrimary = $this->to[0];
        $mailableClass = $mailable::class;
        $mailType = $mailable instanceof DefinesTenantMailMetadata
            ? $mailable->tenantMailType()
            : class_basename($mailableClass);
        $mailGroup = $mailable instanceof DefinesTenantMailMetadata
            ? $mailable->tenantMailGroup()
            : 'transactional';

        $subject = $this->safeSubject($mailable);

        $correlationId = (string) Str::uuid();
        $perMinute = $this->mailer->limitResolver()->resolvePerMinuteForTenant($this->tenant);

        $log = TenantMailLog::query()->create([
            'tenant_id' => $this->tenant->id,
            'correlation_id' => $correlationId,
            'queue_job_id' => null,
            'mailable_class' => $mailableClass,
            'mail_type' => Str::limit($mailType, 100, ''),
            'mail_group' => Str::limit($mailGroup, 64, ''),
            'to_email' => Str::limit($toPrimary, 255, ''),
            'subject' => $subject !== null ? Str::limit($subject, 255, '') : null,
            'status' => TenantMailLog::STATUS_QUEUED,
            'queued_at' => now(),
        ]);

        Log::info('tenant_mail.queued', [
            'tenant_id' => $this->tenant->id,
            'correlation_id' => $correlationId,
            'mail_log_id' => $log->id,
            'mailable_class' => $mailableClass,
            'mail_type' => $mailType,
            'to_email' => $toPrimary,
        ]);

        SendTenantMailableJob::dispatch(
            tenantId: $this->tenant->id,
            mailLogId: $log->id,
            mailable: $mailable,
            to: $this->to,
            mailRateLimitPerMinute: $perMinute,
            correlationId: $correlationId,
        );
    }

    private function safeSubject(Mailable $mailable): ?string
    {
        try {
            return $mailable->envelope()->subject;
        } catch (Throwable) {
            return null;
        }
    }
}
