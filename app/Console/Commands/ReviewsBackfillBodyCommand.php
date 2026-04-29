<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Review;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class ReviewsBackfillBodyCommand extends Command
{
    protected $signature = 'reviews:backfill-body
                            {--dry-run : Show statistics without writing}
                            {--force : Overwrite non-empty body from legacy columns}
                            {--tenant= : Limit to tenant id}';

    protected $description = 'Backfill reviews.body from text_long → text_short → text (idempotent unless --force)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $tenantFilter = $this->option('tenant');
        $tenantFilter = $tenantFilter !== null && $tenantFilter !== '' ? (int) $tenantFilter : null;

        $global = [
            'total' => 0,
            'body_already_filled' => 0,
            'from_text_long' => 0,
            'from_text_short' => 0,
            'from_text' => 0,
            'empty_after' => 0,
            'updated' => 0,
        ];

        $byTenant = [];

        $query = Review::query()->withoutGlobalScopes()->orderBy('id');
        if ($tenantFilter !== null) {
            $query->where('tenant_id', $tenantFilter);
        }

        $query->chunkById(200, function ($reviews) use (&$global, &$byTenant, $dryRun, $force): void {
            foreach ($reviews as $review) {
                /** @var Review $review */
                $global['total']++;
                $tid = (int) ($review->tenant_id ?? 0);
                if (! isset($byTenant[$tid])) {
                    $byTenant[$tid] = [
                        'reviews_total' => 0,
                        'body_already_filled' => 0,
                        'from_text_long' => 0,
                        'from_text_short' => 0,
                        'from_text' => 0,
                        'empty_after' => 0,
                        'updated' => 0,
                    ];
                }
                $byTenant[$tid]['reviews_total']++;

                $bodyTrim = trim((string) ($review->body ?? ''));
                $bodyFilled = $bodyTrim !== '';

                if ($bodyFilled && ! $force) {
                    $global['body_already_filled']++;
                    $byTenant[$tid]['body_already_filled']++;

                    continue;
                }

                $source = Review::resolveLegacyBodySourceColumn($review);
                if ($source === null) {
                    if (! $bodyFilled) {
                        $global['empty_after']++;
                        $byTenant[$tid]['empty_after']++;
                    }

                    continue;
                }

                $global['from_'.$source]++;
                $byTenant[$tid]['from_'.$source]++;
                $global['updated']++;
                $byTenant[$tid]['updated']++;

                if (! $dryRun) {
                    $value = trim((string) $review->getAttribute($source));
                    DB::table('reviews')->where('id', $review->id)->update([
                        'body' => $value,
                        'updated_at' => now(),
                    ]);
                }
            }
        });

        $this->info('Reviews body backfill'.($dryRun ? ' (dry-run)' : '').($force ? ' (--force)' : ''));

        foreach ($byTenant as $tid => $t) {
            $this->line("Tenant #{$tid}:");
            $this->line('  reviews total: '.$t['reviews_total']);
            $this->line('  body already filled: '.$t['body_already_filled']);
            $this->line('  from text_long: '.$t['from_text_long']);
            $this->line('  from text_short: '.$t['from_text_short']);
            $this->line('  from text: '.$t['from_text']);
            $this->line('  empty after migration: '.$t['empty_after']);
            $this->line('  rows updated: '.$t['updated']);
        }

        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['reviews total', $global['total']],
                ['body already filled (skipped)', $global['body_already_filled']],
                ['from text_long', $global['from_text_long']],
                ['from text_short', $global['from_text_short']],
                ['from text (legacy)', $global['from_text']],
                ['empty after (no legacy)', $global['empty_after']],
                ['updated', $global['updated']],
            ],
        );

        $publishedEmptyQ = Review::query()->withoutGlobalScopes()
            ->where('status', 'published')
            ->where(function ($q): void {
                $q->whereNull('body')->orWhere('body', '');
            });
        if ($tenantFilter !== null) {
            $publishedEmptyQ->where('tenant_id', $tenantFilter);
        }
        $publishedEmpty = $publishedEmptyQ->count();
        $this->line('Published with empty body: '.$publishedEmpty);

        if ($global['empty_after'] > 0 || $publishedEmpty > 0) {
            $this->warn('Backfill completed with warnings (empty body rows or published without body).');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
