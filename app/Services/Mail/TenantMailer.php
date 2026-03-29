<?php

namespace App\Services\Mail;

use App\Models\Tenant;
use Illuminate\Support\Arr;

/**
 * Central entrypoint for tenant-scoped mail: queue job + analytics + rate limits.
 *
 * Usage:
 *   app(TenantMailer::class)->to('user@example.com')->queue(new SomeMailable(...));
 *   app(TenantMailer::class)->forTenant($tenant)->to(['a@b.c'])->queue(new SomeMailable(...));
 */
final class TenantMailer
{
    public function __construct(
        private readonly TenantMailLimitResolver $limitResolver,
    ) {}

    public function limitResolver(): TenantMailLimitResolver
    {
        return $this->limitResolver;
    }

    public function forTenant(Tenant $tenant): TenantMailPartialBuilder
    {
        return new TenantMailPartialBuilder($this, $tenant);
    }

    /**
     * @param  string|array<int, string>  $emails
     */
    public function to(string|array $emails): TenantMailQueueBuilder
    {
        $tenant = currentTenant();
        if ($tenant === null) {
            throw new \LogicException(
                'Tenant mail requires an explicit tenant (forTenant) or HTTP tenant context (currentTenant).'
            );
        }

        $normalized = $this->normalizeEmails($emails);
        if ($normalized === []) {
            throw new \LogicException('Tenant mail requires at least one recipient email.');
        }

        return new TenantMailQueueBuilder($this, $tenant, $normalized);
    }

    /**
     * @param  string|array<int, string>  $emails
     * @return list<string>
     */
    public function normalizeEmails(string|array $emails): array
    {
        $list = array_values(array_filter(array_map(
            static fn (mixed $e): string => is_string($e) ? trim($e) : '',
            Arr::wrap($emails)
        ), static fn (string $e): bool => $e !== ''));

        return array_values(array_unique($list));
    }
}
