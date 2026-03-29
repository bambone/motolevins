<?php

namespace App\Jobs\Mail\Middleware;

use App\Jobs\Mail\SendTenantMailableJob;
use App\Models\TenantMailLog;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\Log;

/**
 * Extends core RateLimited to record throttle events on tenant_mail_logs.
 */
class TenantMailRateLimited extends RateLimited
{
    protected function handleJob($job, $next, array $limits)
    {
        foreach ($limits as $limit) {
            if ($this->limiter->tooManyAttempts($limit->key, $limit->maxAttempts)) {
                if ($job instanceof SendTenantMailableJob && $job->mailLogId !== null) {
                    TenantMailLog::query()->whereKey($job->mailLogId)->update([
                        'status' => TenantMailLog::STATUS_DEFERRED,
                    ]);
                    TenantMailLog::query()->whereKey($job->mailLogId)->increment('throttled_count');
                    Log::info('tenant_mail.throttled', [
                        'tenant_id' => $job->tenantId,
                        'mail_log_id' => $job->mailLogId,
                        'correlation_id' => $job->correlationId,
                        'limit_per_minute' => $job->mailRateLimitPerMinute,
                    ]);
                }

                return $this->shouldRelease
                    ? $job->release($this->releaseAfter ?: $this->getTimeUntilNextRetry($limit->key))
                    : false;
            }

            $this->limiter->hit($limit->key, $limit->decaySeconds);
        }

        return $next($job);
    }
}
