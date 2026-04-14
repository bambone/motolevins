<?php

namespace App\Money\Enums;

enum MoneyFractionDisplayMode: string
{
    case Auto = 'auto';

    case Always = 'always';

    case Never = 'never';
}
