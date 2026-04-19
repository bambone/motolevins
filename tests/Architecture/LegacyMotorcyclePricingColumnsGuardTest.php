<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * After legacy columns are dropped from DB and code is migrated, set LEGACY_MOTORCYCLE_PRICING_DROPPED=true in phpunit.xml
 * to enforce zero references outside whitelisted paths.
 */
#[Group('legacy-pricing-drop')]
final class LegacyMotorcyclePricingColumnsGuardTest extends TestCase
{
    public function test_no_direct_legacy_column_references_after_cutover(): void
    {
        if (env('LEGACY_MOTORCYCLE_PRICING_DROPPED') !== 'true') {
            $this->markTestSkipped('Enable LEGACY_MOTORCYCLE_PRICING_DROPPED=true after drop migration.');
        }

        $needles = [
            '->price_per_day',
            '->price_2_3_days',
            '->price_week',
            '->catalog_price_note',
            'price_per_day_snapshot',
        ];

        $finder = Finder::create()
            ->files()
            ->name('*.php')
            ->notPath('vendor')
            ->in(base_path('app'))
            ->in(base_path('resources/views'))
            ->in(base_path('tests'));

        $hits = [];
        foreach ($finder as $file) {
            $path = $file->getRealPath();
            if ($path === false) {
                continue;
            }
            if (str_contains($path, 'LegacyMotorcyclePricingProfileFactory.php')
                || str_contains($path, 'MotorcyclePricingProfileLegacyScalarSync.php')
                || str_contains($path, 'BackfillMotorcyclePricingProfilesCommand.php')
                || str_contains($path, 'LegacyMotorcyclePricingColumnsGuardTest.php')) {
                continue;
            }
            $contents = (string) file_get_contents($path);
            foreach ($needles as $n) {
                if (str_contains($contents, $n)) {
                    $hits[] = $path.': '.$n;
                }
            }
        }

        $this->assertSame([], $hits, "Legacy pricing references:\n".implode("\n", $hits));
    }
}
