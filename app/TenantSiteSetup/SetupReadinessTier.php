<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

enum SetupReadinessTier: string
{
    /** Минимальный набор для честного «быстрого запуска». */
    case QuickLaunch = 'quick_launch';

    /** Дополнительные шаги после базового контура. */
    case Extended = 'extended';
}
