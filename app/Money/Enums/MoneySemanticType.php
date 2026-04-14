<?php

namespace App\Money\Enums;

enum MoneySemanticType: string
{
    case Price = 'price';

    case Deposit = 'deposit';

    case Fee = 'fee';

    case Discount = 'discount';

    case Other = 'other';
}
