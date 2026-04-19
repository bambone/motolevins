<?php

declare(strict_types=1);

namespace Tests\Unit\MotorcyclePricing;

use App\MotorcyclePricing\MotorcycleTariffResolver;
use App\MotorcyclePricing\TariffKind;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class MotorcycleTariffResolverTest extends TestCase
{
    #[Test]
    public function it_prefers_narrow_duration_range_over_always(): void
    {
        $resolver = new MotorcycleTariffResolver;
        $tariffs = [
            [
                'id' => 'always',
                'kind' => TariffKind::FixedPerDay->value,
                'amount_minor' => 10_000,
                'applicability' => ['mode' => 'always'],
                'priority' => 500,
            ],
            [
                'id' => 'range',
                'kind' => TariffKind::FixedPerDay->value,
                'amount_minor' => 8_000,
                'applicability' => ['mode' => 'duration_range_days', 'min_days' => 2, 'max_days' => 3],
                'priority' => 500,
            ],
        ];

        $out = $resolver->resolveForAutoQuote($tariffs, 2);

        $this->assertFalse($out['conflict']);
        $this->assertSame('range', $out['tariff']['id'] ?? null);
    }
}
