<?php

namespace App\Product\CRM;

use App\Models\CrmRequest;
use App\Models\Lead;

final class CrmRequestCreationResult
{
    public function __construct(
        public readonly CrmRequest $crmRequest,
        public readonly ?Lead $lead = null,
    ) {}
}
