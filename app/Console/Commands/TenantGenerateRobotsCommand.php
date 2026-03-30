<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ResolvesTenantArgument;
use App\Models\TenantSeoFile;
use App\Models\TenantSeoFileGeneration;
use App\Services\Seo\TenantSeoFilePublisher;
use App\Services\Seo\TenantSeoSnapshotReader;
use Illuminate\Console\Command;
use Throwable;

class TenantGenerateRobotsCommand extends Command
{
    use ResolvesTenantArgument;

    protected $signature = 'tenant:generate-robots {tenant : Tenant ID or slug} {--force : Overwrite existing snapshot}';

    protected $description = 'Generate and publish tenant robots.txt snapshot';

    public function handle(TenantSeoFilePublisher $publisher, TenantSeoSnapshotReader $reader): int
    {
        $tenant = $this->resolveTenant((string) $this->argument('tenant'));
        $had = $reader->readValid($tenant->id, TenantSeoFile::TYPE_ROBOTS_TXT) !== null;

        if ($had && ! $this->option('force')) {
            $this->error('Snapshot exists. Re-run with --force to overwrite.');

            return self::FAILURE;
        }

        try {
            $publisher->publishRobots(
                $tenant,
                null,
                TenantSeoFileGeneration::SOURCE_SYSTEM,
                $had,
                false,
            );
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('robots.txt published for tenant '.$tenant->slug.' (id '.$tenant->id.').');

        return self::SUCCESS;
    }
}
