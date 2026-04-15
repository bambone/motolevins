<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\TenantDomain;
use App\Services\Tenancy\TenantDomainHostRules;
use Illuminate\Console\Command;

class AuditInvalidTenantDomainsCommand extends Command
{
    protected $signature = 'tenancy:audit-invalid-tenant-domains';

    protected $description = 'Report tenant_domains rows with invalid host format or reserved hosts (AF-001 data audit)';

    public function handle(TenantDomainHostRules $rules): int
    {
        $rows = TenantDomain::query()
            ->orderBy('id')
            ->get(['id', 'tenant_id', 'host', 'type']);

        $table = [];

        foreach ($rows as $row) {
            $canonical = $rules->tryCanonicalizeFormat((string) $row->host, (string) $row->type);
            $reasons = [];

            if ($canonical === null) {
                $reasons[] = 'invalid_format';
            } else {
                foreach ($rules->diagnoseStoredHostIssues((string) $row->host, (string) $row->type) as $issue) {
                    $reasons[] = $issue['issue'];
                }

                if ($canonical !== (string) $row->host) {
                    $reasons[] = 'not_canonical_stored';
                }
            }

            if ($reasons === []) {
                continue;
            }

            $table[] = [
                'id' => $row->id,
                'tenant_id' => $row->tenant_id,
                'host' => $row->host,
                'type' => $row->type,
                'reasons' => implode(', ', array_unique($reasons)),
                'canonical_if_obvious' => $canonical ?? '—',
            ];
        }

        if ($table === []) {
            $this->info('No invalid or non-canonical tenant_domains hosts found.');

            return self::SUCCESS;
        }

        $this->warn('Found '.count($table).' row(s) needing review:');
        $this->table(
            ['id', 'tenant_id', 'host', 'type', 'reasons', 'canonical_if_obvious'],
            $table
        );

        return self::SUCCESS;
    }
}
