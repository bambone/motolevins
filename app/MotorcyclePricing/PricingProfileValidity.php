<?php

declare(strict_types=1);

namespace App\MotorcyclePricing;

enum PricingProfileValidity: string
{
    case Valid = 'valid';
    case ValidWithWarnings = 'valid_with_warnings';
    case Invalid = 'invalid';
}
