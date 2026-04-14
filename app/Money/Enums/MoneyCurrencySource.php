<?php

namespace App\Money\Enums;

enum MoneyCurrencySource: string
{
    case TenantBase = 'tenant_base_currency';

    case Fixed = 'fixed_currency';
}
