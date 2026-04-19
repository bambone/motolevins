<?php

declare(strict_types=1);

namespace App\MotorcyclePricing;

use Carbon\Carbon;

/**
 * Single contract for inclusive calendar-day rental length (checkout UI, quote API, PricingService, hydrators).
 */
final class RentalPricingDuration
{
    /**
     * Inclusive count of calendar days from start date through end date (both at calendar-day precision).
     */
    public static function inclusiveCalendarDays(Carbon $start, Carbon $end): int
    {
        $a = $start->copy()->startOfDay();
        $b = $end->copy()->startOfDay();

        return (int) max(1, $a->diffInDays($b) + 1);
    }
}
