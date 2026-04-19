<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Motorcycle;
use App\MotorcyclePricing\MotorcyclePricingProfileLoader;
use App\MotorcyclePricing\MotorcyclePricingProfileValidator;
use App\MotorcyclePricing\PricingProfileValidity;
use Illuminate\Console\Command;

/**
 * Operational gate: no invalid motorcycle pricing profiles in scope before enabling cutover env flags.
 */
class PricingCutoverAcceptanceGateCommand extends Command
{
    protected $signature = 'pricing:cutover-acceptance-gate
                            {--tenant= : Limit to tenant_id}
                            {--allow-warnings : Succeed when only valid_with_warnings remain (invalid must still be 0)}';

    protected $description = 'Validate motorcycle pricing profiles (loaded or synthesized). Fails if any are invalid unless you fix data first.';

    public function handle(MotorcyclePricingProfileLoader $loader, MotorcyclePricingProfileValidator $validator): int
    {
        $tenantId = $this->option('tenant');
        $allowWarnings = (bool) $this->option('allow-warnings');

        $q = Motorcycle::query()->orderBy('id');
        if ($tenantId !== null && $tenantId !== '') {
            $q->where('tenant_id', (int) $tenantId);
        }

        $valid = 0;
        $warnings = 0;
        $invalid = 0;
        /** @var list<int> $invalidIds */
        $invalidIds = [];
        /** @var list<int> $warningIds */
        $warningIds = [];

        foreach ($q->cursor() as $m) {
            $profile = $loader->loadOrSynthesize($m);
            if ($profile === []) {
                $invalid++;
                $invalidIds[] = (int) $m->id;

                continue;
            }

            $v = $validator->validate($profile);
            if ($v['validity'] === PricingProfileValidity::Invalid) {
                $invalid++;
                $invalidIds[] = (int) $m->id;
            } elseif ($v['validity'] === PricingProfileValidity::ValidWithWarnings) {
                $warnings++;
                $warningIds[] = (int) $m->id;
            } else {
                $valid++;
            }
        }

        $this->table(
            ['metric', 'count'],
            [
                ['valid', (string) $valid],
                ['valid_with_warnings', (string) $warnings],
                ['invalid', (string) $invalid],
            ],
        );

        if ($invalidIds !== []) {
            $this->error('Invalid motorcycle ids: '.implode(', ', array_slice($invalidIds, 0, 200)).(count($invalidIds) > 200 ? ' …' : ''));
        }

        if ($invalid > 0) {
            return self::FAILURE;
        }

        if (! $allowWarnings && $warnings > 0) {
            $this->warn('Warnings remain (use --allow-warnings after review). Ids: '.implode(', ', array_slice($warningIds, 0, 200)).(count($warningIds) > 200 ? ' …' : ''));

            return self::FAILURE;
        }

        $this->info('Acceptance gate passed for scoped motorcycles.');

        return self::SUCCESS;
    }
}
