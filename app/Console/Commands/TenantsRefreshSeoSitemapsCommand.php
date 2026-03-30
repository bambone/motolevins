<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Services\Seo\SitemapFreshnessService;
use App\Services\Seo\TenantSeoFilePublisher;
use Illuminate\Console\Command;
use Throwable;

class TenantsRefreshSeoSitemapsCommand extends Command
{
    protected $signature = 'tenants:refresh-seo-sitemaps {--stale-only : Only tenants with missing/stale sitemap}';

    protected $description = 'Bulk refresh tenant sitemap.xml snapshots (continues on per-tenant errors)';

    public function handle(TenantSeoFilePublisher $publisher, SitemapFreshnessService $freshness): int
    {
        $failures = [];
        $success = 0;
        $skipped = 0;

        $query = Tenant::query()->where('status', 'active')->orderBy('id');

        foreach ($query->cursor() as $tenant) {
            if (! (bool) TenantSetting::getForTenant($tenant->id, 'seo.sitemap_auto_regenerate_on_schedule', false)) {
                $skipped++;

                continue;
            }

            if ($this->option('stale-only')) {
                $status = $freshness->resolveStatus($tenant);
                if (! in_array($status, [
                    SitemapFreshnessService::STATUS_MISSING,
                    SitemapFreshnessService::STATUS_STALE_CONTENT,
                    SitemapFreshnessService::STATUS_STALE_AGE,
                ], true)) {
                    continue;
                }
            }

            try {
                $publisher->publishSitemap(
                    $tenant,
                    null,
                    TenantSeoFileGeneration::SOURCE_SCHEDULE,
                    false,
                );
                $success++;
            } catch (Throwable $e) {
                $msg = 'Tenant '.$tenant->id.' ('.$tenant->slug.'): '.$e->getMessage();
                $failures[] = $msg;
                $this->error($msg);
            }
        }

        $this->line('Done. Success: '.$success.', skipped (schedule off): '.$skipped.', failures: '.count($failures).'.');
        if ($failures !== []) {
            $this->line('Failures:');
            foreach ($failures as $f) {
                $this->line(' - '.$f);
            }

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
