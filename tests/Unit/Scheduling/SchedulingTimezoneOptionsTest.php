<?php

declare(strict_types=1);

namespace Tests\Unit\Scheduling;

use App\Scheduling\SchedulingTimezoneOptions;
use PHPUnit\Framework\TestCase;

class SchedulingTimezoneOptionsTest extends TestCase
{
    public function test_normalize_to_known_returns_canonical_identifier(): void
    {
        $this->assertSame('Europe/Moscow', SchedulingTimezoneOptions::normalizeToKnown('europe/moscow'));
        $this->assertSame('UTC', SchedulingTimezoneOptions::normalizeToKnown('UTC'));
    }

    public function test_normalize_to_known_falls_back_for_garbage(): void
    {
        $this->assertSame(
            SchedulingTimezoneOptions::DEFAULT_IDENTIFIER,
            SchedulingTimezoneOptions::normalizeToKnown('Not/A/Zone')
        );
    }
}
