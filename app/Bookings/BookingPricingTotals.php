<?php

declare(strict_types=1);

namespace App\Bookings;

use App\Models\Booking;
use App\MotorcyclePricing\MotorcyclePricingSchema;
use App\MotorcyclePricing\PricingMinorMoney;

/**
 * Чтение сумм брони: предпочитает v2 snapshot и minor-скаляры, иначе legacy scalar.
 */
final class BookingPricingTotals
{
    public static function grandTotalMajor(Booking $booking): int
    {
        if (self::hasV2Snapshot($booking)) {
            $grand = $booking->pricing_snapshot_json['totals']['grand_total_minor'] ?? null;
            if ($grand !== null && (int) $grand > 0) {
                return PricingMinorMoney::minorToMajor((int) $grand);
            }
        }

        return (int) $booking->total_price;
    }

    public static function rentalTotalMajor(Booking $booking): int
    {
        if ($booking->rental_total_minor !== null && (int) $booking->rental_total_minor > 0) {
            return PricingMinorMoney::minorToMajor((int) $booking->rental_total_minor);
        }

        if (self::hasV2Snapshot($booking)) {
            $rent = $booking->pricing_snapshot_json['totals']['rental_total_minor'] ?? null;
            if ($rent !== null && (int) $rent >= 0) {
                return PricingMinorMoney::minorToMajor((int) $rent);
            }
        }

        $addons = (int) $booking->addons()->get()->sum(
            fn ($a) => (int) $a->price_snapshot * (int) $a->quantity,
        );

        return max(0, (int) $booking->total_price - $addons);
    }

    public static function pricePerDaySnapshotMajor(Booking $booking): int
    {
        if (self::hasV2Snapshot($booking)) {
            $lines = $booking->pricing_snapshot_json['lines'] ?? [];
            if (is_array($lines)) {
                foreach ($lines as $line) {
                    if (! is_array($line)) {
                        continue;
                    }
                    if (($line['type'] ?? '') === 'rental' && (int) ($line['quantity'] ?? 0) > 0) {
                        return PricingMinorMoney::minorToMajor((int) ($line['unit_amount_minor'] ?? 0));
                    }
                }
            }
        }

        return (int) $booking->price_per_day_snapshot;
    }

    public static function depositMajor(Booking $booking): int
    {
        if ($booking->deposit_amount_minor !== null && (int) $booking->deposit_amount_minor > 0) {
            return PricingMinorMoney::minorToMajor((int) $booking->deposit_amount_minor);
        }

        return (int) $booking->deposit_amount;
    }

    private static function hasV2Snapshot(Booking $booking): bool
    {
        return (int) $booking->pricing_snapshot_schema_version === MotorcyclePricingSchema::SNAPSHOT_VERSION
            && is_array($booking->pricing_snapshot_json)
            && $booking->pricing_snapshot_json !== [];
    }
}
