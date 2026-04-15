<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Accessibility\WcagContrast;
use PHPUnit\Framework\TestCase;

final class WcagContrastTest extends TestCase
{
    public function test_ratio_is_symmetric(): void
    {
        $a = WcagContrast::ratio('#ffffff', '#000000');
        $b = WcagContrast::ratio('#000000', '#ffffff');
        $this->assertNotNull($a);
        $this->assertNotNull($b);
        $this->assertEqualsWithDelta($a, $b, 0.0001);
        $this->assertEqualsWithDelta(21.0, $a, 0.05);
    }

    public function test_invalid_hex_returns_null(): void
    {
        $this->assertNull(WcagContrast::ratio('#gg0000', '#000000'));
        $this->assertNull(WcagContrast::relativeLuminance(''));
    }

    public function test_brand_amber_on_near_black_meets_aa_for_ui_text(): void
    {
        $ratio = WcagContrast::ratio('#f59e0b', '#0c0c0c');
        $this->assertNotNull($ratio);
        $this->assertGreaterThan(4.5, $ratio);
    }

    public function test_shorthand_hex_expands(): void
    {
        $full = WcagContrast::ratio('#ff0000', '#ffffff');
        $short = WcagContrast::ratio('#f00', '#fff');
        $this->assertNotNull($full);
        $this->assertNotNull($short);
        $this->assertEqualsWithDelta($full, $short, 0.0001);
    }
}
