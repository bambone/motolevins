<?php

declare(strict_types=1);

namespace Tests\Unit\Tenant\BlackDuck;

use App\Tenant\BlackDuck\BlackDuckContentConstants;
use App\Tenant\BlackDuck\BlackDuckServiceRegistry;
use Tests\TestCase;

final class BlackDuckServiceRegistryTest extends TestCase
{
    public function test_legacy_matrix_matches_content_constants_contract(): void
    {
        $legacy = BlackDuckServiceRegistry::legacyMatrixQ1();
        $fromConstants = BlackDuckContentConstants::serviceMatrixQ1();
        $this->assertSame($legacy, $fromConstants);
    }

    public function test_catalog_has_six_tz_groups(): void
    {
        $groups = BlackDuckServiceRegistry::catalogGroupsWithPlaceholderItems();
        $this->assertCount(6, $groups);
    }

    public function test_public_price_anchors_match_confirmed_slugs(): void
    {
        $this->assertSame('от 10 000 ₽', BlackDuckServiceRegistry::publicPriceAnchorForSlug('ppf'));
        $this->assertSame('от 2 000 ₽', BlackDuckServiceRegistry::publicPriceAnchorForSlug('detejling-mojka'));
        $this->assertNull(BlackDuckServiceRegistry::publicPriceAnchorForSlug('tonirovka'));
    }

    public function test_deferred_tz_slugs_have_reserved_price_anchors(): void
    {
        foreach (BlackDuckServiceRegistry::deferredTzServiceSlugs() as $slug) {
            $this->assertNotNull(
                BlackDuckServiceRegistry::publicPriceAnchorForSlug($slug),
                'Reserved anchor for deferred slug: '.$slug,
            );
        }
    }

    public function test_min_works_portfolio_acceptance_constant(): void
    {
        $this->assertSame(12, BlackDuckServiceRegistry::MIN_WORKS_PORTFOLIO_ITEMS_ACCEPTANCE);
    }
}
