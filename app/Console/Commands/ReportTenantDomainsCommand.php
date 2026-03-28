<?php

namespace App\Console\Commands;

use App\Models\TenantDomain;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReportTenantDomainsCommand extends Command
{
    protected $signature = 'tenancy:report-domains';

    protected $description = 'Summary of tenant_domains rows by status (pre-release sanity check)';

    public function handle(): int
    {
        $total = TenantDomain::query()->count();
        $this->info('Total tenant_domains: '.$total);

        $byStatus = TenantDomain::query()
            ->select('status', DB::raw('count(*) as c'))
            ->groupBy('status')
            ->orderBy('status')
            ->pluck('c', 'status');

        $this->table(['status', 'count'], $byStatus->map(fn ($c, $s) => [$s ?? '(null)', $c])->values()->all());

        $bySsl = TenantDomain::query()
            ->select('ssl_status', DB::raw('count(*) as c'))
            ->groupBy('ssl_status')
            ->orderBy('ssl_status')
            ->pluck('c', 'ssl_status');

        $this->table(['ssl_status', 'count'], $bySsl->map(fn ($c, $s) => [$s ?? '(null)', $c])->values()->all());

        $nullStatus = TenantDomain::query()->whereNull('status')->count();
        if ($nullStatus > 0) {
            $this->warn('Rows with NULL status: '.$nullStatus);
        }

        $nonActive = TenantDomain::query()->where('status', '!=', TenantDomain::STATUS_ACTIVE)->count();
        $this->line('Non-active (will not resolve publicly): '.$nonActive);

        return self::SUCCESS;
    }
}
