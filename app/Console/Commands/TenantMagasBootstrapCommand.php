<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Database\Seeders\Tenant\MagasExpertBootstrap;
use Illuminate\Console\Command;

/**
 * Idempotent onboarding for Sergei Magas tenant ({@see MagasExpertBootstrap}). Not chained from {@see \Database\Seeders\DatabaseSeeder}.
 */
final class TenantMagasBootstrapCommand extends Command
{
    protected $signature = 'tenant:magas:bootstrap';

    protected $description = 'Create or refresh demo/bootstrap content for tenant sergey-magas (expert_pr).';

    public function handle(): int
    {
        MagasExpertBootstrap::run();
        $this->info('Magas tenant bootstrap completed (slug '.MagasExpertBootstrap::SLUG.').');

        return self::SUCCESS;
    }
}
