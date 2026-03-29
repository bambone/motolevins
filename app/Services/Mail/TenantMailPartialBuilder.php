<?php

namespace App\Services\Mail;

use App\Models\Tenant;
use Illuminate\Mail\Mailable;

/**
 * Fluent chain after forTenant(): ->to(...)->queue(...).
 */
final class TenantMailPartialBuilder
{
    public function __construct(
        private readonly TenantMailer $mailer,
        private readonly Tenant $tenant,
    ) {}

    /**
     * @param  string|array<int, string>  $emails
     */
    public function to(string|array $emails): TenantMailQueueBuilder
    {
        $normalized = $this->mailer->normalizeEmails($emails);
        if ($normalized === []) {
            throw new \LogicException('Tenant mail requires at least one recipient email.');
        }

        return new TenantMailQueueBuilder($this->mailer, $this->tenant, $normalized);
    }

    public function queue(Mailable $mailable, string|array $to): void
    {
        $this->to($to)->queue($mailable);
    }
}
