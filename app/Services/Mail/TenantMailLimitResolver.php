<?php

namespace App\Services\Mail;

use App\Models\Tenant;

/**
 * Resolves effective per-minute mail cap for a tenant (admin field today; plan/tariff later).
 */
final class TenantMailLimitResolver
{
    public function resolvePerMinuteForTenant(Tenant $tenant): int
    {
        $raw = $tenant->mail_rate_limit_per_minute;
        if ($raw === null) {
            return $this->defaultPerMinute();
        }

        $n = (int) $raw;
        if ($n < 1) {
            return $this->defaultPerMinute();
        }

        $max = (int) config('mail_limits.max_per_minute', 1000);

        return min($max, $n);
    }

    public function defaultPerMinute(): int
    {
        $d = (int) config('mail_limits.default_per_minute', 10);
        $min = (int) config('mail_limits.min_per_minute', 1);
        $max = (int) config('mail_limits.max_per_minute', 1000);

        return max($min, min($max, $d));
    }
}
