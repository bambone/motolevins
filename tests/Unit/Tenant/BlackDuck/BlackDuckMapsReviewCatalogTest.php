<?php

declare(strict_types=1);

namespace Tests\Unit\Tenant\BlackDuck;

use App\Tenant\BlackDuck\BlackDuckMapsReviewCatalog;
use App\Tenant\BlackDuck\BlackDuckServiceRegistry;
use Tests\TestCase;

final class BlackDuckMapsReviewCatalogTest extends TestCase
{
    public function test_pool_size_matches_landing_slugs_times_distribution(): void
    {
        $pool = BlackDuckMapsReviewCatalog::pool();
        $slugs = BlackDuckMapsReviewCatalog::landingSlugOrder();
        $this->assertNotEmpty($slugs);
        $n = count($slugs);
        $expected = count($pool);
        $base = intdiv($expected, $n);
        $rem = $expected % $n;
        $this->assertSame($expected, $rem * ($base + 1) + ($n - $rem) * $base);
        $rows = BlackDuckMapsReviewCatalog::rowsForDatabaseSeed();
        $this->assertCount($expected, $rows);
        foreach ($slugs as $slug) {
            $c = 0;
            foreach ($rows as $r) {
                if (($r['category_key'] ?? '') === $slug) {
                    $c++;
                }
            }
            $this->assertContains($c, [$base, $base + 1]);
        }
    }

    public function test_landing_slug_order_matches_registry_landings(): void
    {
        $fromCatalog = BlackDuckMapsReviewCatalog::landingSlugOrder();
        $fromRegistry = [];
        foreach (BlackDuckServiceRegistry::all() as $r) {
            if ($r['has_landing'] && ! str_starts_with((string) $r['slug'], '#')) {
                $fromRegistry[] = $r['slug'];
            }
        }
        $this->assertSame($fromRegistry, $fromCatalog);
    }
}
