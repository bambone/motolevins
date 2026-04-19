<?php

declare(strict_types=1);

namespace Tests\Unit\Bookings;

use App\Bookings\BookingPricingTotals;
use App\Models\Booking;
use App\MotorcyclePricing\MotorcyclePricingSchema;
use App\MotorcyclePricing\PricingMinorMoney;
use Tests\TestCase;

final class BookingPricingTotalsTest extends TestCase
{
    public function test_grand_total_prefers_v2_snapshot(): void
    {
        $grandMinor = PricingMinorMoney::majorToMinor(15_000);
        $booking = new Booking([
            'total_price' => 999,
            'pricing_snapshot_schema_version' => MotorcyclePricingSchema::SNAPSHOT_VERSION,
            'pricing_snapshot_json' => [
                'totals' => ['grand_total_minor' => $grandMinor],
            ],
        ]);

        $this->assertSame(15_000, BookingPricingTotals::grandTotalMajor($booking));
    }

    public function test_grand_total_falls_back_to_total_price_when_no_v2_snapshot(): void
    {
        $booking = new Booking([
            'total_price' => 12_000,
            'pricing_snapshot_schema_version' => 0,
        ]);

        $this->assertSame(12_000, BookingPricingTotals::grandTotalMajor($booking));
    }

    public function test_deposit_prefers_minor_scalar(): void
    {
        $booking = new Booking([
            'deposit_amount' => 1,
            'deposit_amount_minor' => PricingMinorMoney::majorToMinor(5000),
        ]);

        $this->assertSame(5000, BookingPricingTotals::depositMajor($booking));
    }
}
