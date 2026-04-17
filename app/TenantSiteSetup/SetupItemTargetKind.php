<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

enum SetupItemTargetKind: string
{
    case Field = 'field';
    case Section = 'section';
    case Action = 'action';
    case Page = 'page';
}
