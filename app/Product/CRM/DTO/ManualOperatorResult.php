<?php

namespace App\Product\CRM\DTO;

use App\Models\Booking;
use App\Models\CrmRequest;
use App\Models\Lead;

final class ManualOperatorResult
{
    public function __construct(
        public readonly ?Lead $lead = null,
        public readonly ?CrmRequest $crmRequest = null,
        public readonly ?Booking $booking = null,
    ) {}
}
