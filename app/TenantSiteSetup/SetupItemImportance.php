<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

enum SetupItemImportance: string
{
    case Critical = 'critical';
    case Recommended = 'recommended';
    case Advanced = 'advanced';
}
