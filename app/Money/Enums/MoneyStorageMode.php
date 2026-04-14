<?php

namespace App\Money\Enums;

enum MoneyStorageMode: string
{
    /** Integer major units (e.g. whole rubles in DB). */
    case MajorInteger = 'major_integer';

    /** Integer minor units (e.g. kopecks in DB). */
    case MinorInteger = 'minor_integer';
}
