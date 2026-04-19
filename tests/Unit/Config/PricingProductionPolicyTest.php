<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use Tests\TestCase;

/**
 * В CI для cutover-деплоя: ASSERT_PRICING_CUTOVER=true в phpunit.xml / env.
 *
 * @group production-policy
 */
final class PricingProductionPolicyTest extends TestCase
{
    public function test_legacy_pricing_fallbacks_disabled_when_env_asserts_cutover(): void
    {
        if (env('ASSERT_PRICING_CUTOVER') !== 'true') {
            $this->markTestSkipped('Set ASSERT_PRICING_CUTOVER=true to enforce cutover config in this pipeline.');
        }

        $this->assertFalse(config('pricing.legacy_scalar_price_fallback'));
        $this->assertFalse(config('pricing.legacy_profile_fallback'));
    }
}
