<?php

namespace App\Services\Tenancy;

use App\Models\TenantDomain;
use Symfony\Component\Process\Process;

class DomainProvisioningService
{
    public function provision(TenantDomain $domain): void
    {
        $script = (string) config('tenancy.custom_domains.provision_script', '');
        if ($script === '') {
            throw new \RuntimeException('TENANCY_PROVISION_SCRIPT is not configured.');
        }

        $useSudo = (bool) config('tenancy.provision_use_sudo', false);
        $command = $useSudo ? ['sudo', '-n', $script] : [$script];

        $primary = preg_replace('/^www\./i', '', $domain->host) ?? $domain->host;

        if (str_starts_with(mb_strtolower($domain->host), 'www.')) {
            $command[] = $primary;
            $command[] = $domain->host;
        } else {
            $command[] = $primary;
        }

        $process = new Process($command);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            $domain->update([
                'status' => TenantDomain::STATUS_FAILED,
                'ssl_status' => TenantDomain::SSL_FAILED,
            ]);

            throw new \RuntimeException(trim($process->getErrorOutput() ?: $process->getOutput()) ?: 'Provisioning failed.');
        }

        $domain->update([
            'status' => TenantDomain::STATUS_ACTIVE,
            'ssl_status' => TenantDomain::SSL_ISSUED,
            'activated_at' => now(),
        ]);
    }
}
