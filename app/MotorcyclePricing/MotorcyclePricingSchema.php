<?php

declare(strict_types=1);

namespace App\MotorcyclePricing;

final class MotorcyclePricingSchema
{
    public const PROFILE_VERSION = 1;

    public const SNAPSHOT_VERSION = 1;

    /** ISO-4217 default when tenant has no separate currency on profile */
    public const DEFAULT_CURRENCY = 'RUB';
}
