<?php

namespace App\Jobs;

use App\Models\TenantDomain;
use App\Services\Tenancy\DomainProvisioningService;
use App\Services\Tenancy\DomainVerificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProvisionTenantCustomDomainJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $tenantDomainId
    ) {}

    public function handle(
        DomainVerificationService $verificationService,
        DomainProvisioningService $provisioningService
    ): void {
        $domain = TenantDomain::query()->findOrFail($this->tenantDomainId);

        if (! $domain->isCustom()) {
            return;
        }

        if (! $verificationService->dnsFullyVerified($domain)) {
            $domain->update([
                'status' => TenantDomain::STATUS_VERIFYING,
                'last_checked_at' => now(),
            ]);

            return;
        }

        $domain->update([
            'verified_at' => now(),
            'last_checked_at' => now(),
        ]);

        $provisioningService->provision($domain);
    }
}
