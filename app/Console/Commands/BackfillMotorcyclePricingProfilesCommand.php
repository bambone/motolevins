<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Motorcycle;
use App\MotorcyclePricing\LegacyMotorcyclePricingProfileFactory;
use App\MotorcyclePricing\MotorcyclePricingProfileValidator;
use App\MotorcyclePricing\MotorcyclePricingSchema;
use App\MotorcyclePricing\PricingProfileValidity;
use Illuminate\Console\Command;

class BackfillMotorcyclePricingProfilesCommand extends Command
{
    protected $signature = 'motorcycle:backfill-pricing-profiles
                            {--dry-run : Do not write to the database}
                            {--tenant= : Limit to tenant_id}
                            {--fail-on-warnings : Exit failure if any profile is valid_with_warnings}
                            {--report= : Write newline-separated invalid motorcycle ids to this file}';

    protected $description = 'Idempotent backfill: empty pricing_profile_json ← legacy price_* via LegacyMotorcyclePricingProfileFactory; prints validity counts. Does not persist invalid profiles.';

    public function handle(MotorcyclePricingProfileValidator $validator): int
    {
        $dry = (bool) $this->option('dry-run');
        $tenantId = $this->option('tenant');
        $q = Motorcycle::query()->orderBy('id');
        if ($tenantId !== null && $tenantId !== '') {
            $q->where('tenant_id', (int) $tenantId);
        }

        $valid = 0;
        $warnings = 0;
        $invalid = 0;
        $skipped = 0;
        $written = 0;
        /** @var list<int> $invalidIds */
        $invalidIds = [];
        /** @var list<int> $warningIds */
        $warningIds = [];

        foreach ($q->cursor() as $m) {
            $raw = $m->pricing_profile_json;
            if (is_array($raw) && $raw !== []) {
                $skipped++;

                continue;
            }

            $profile = LegacyMotorcyclePricingProfileFactory::fromMotorcycle($m);
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

            $shouldWrite = ! $dry
                && $v['validity'] !== PricingProfileValidity::Invalid;

            if ($shouldWrite) {
                $m->forceFill([
                    'pricing_profile_json' => $profile,
                    'pricing_profile_schema_version' => MotorcyclePricingSchema::PROFILE_VERSION,
                ])->saveQuietly();
                $written++;
            }
        }

        $this->table(
            ['metric', 'count'],
            [
                ['valid', (string) $valid],
                ['valid_with_warnings', (string) $warnings],
                ['invalid', (string) $invalid],
                ['skipped_already_had_profile', (string) $skipped],
                ['rows_written', (string) $written],
            ],
        );

        if ($invalidIds !== []) {
            $this->warn('Invalid profile motorcycle ids: '.implode(', ', array_slice($invalidIds, 0, 200)).(count($invalidIds) > 200 ? ' …' : ''));
        }

        $reportPath = $this->option('report');
        if (is_string($reportPath) && $reportPath !== '' && $invalidIds !== []) {
            file_put_contents($reportPath, implode("\n", $invalidIds)."\n");
            $this->info('Invalid ids written to '.$reportPath);
        }

        if ($dry) {
            $this->info('Dry run: no rows updated.');
        }

        if ($invalid > 0) {
            return self::FAILURE;
        }

        if ((bool) $this->option('fail-on-warnings') && $warnings > 0) {
            $this->warn('fail-on-warnings: ids with warnings: '.implode(', ', array_slice($warningIds, 0, 200)).(count($warningIds) > 200 ? ' …' : ''));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
