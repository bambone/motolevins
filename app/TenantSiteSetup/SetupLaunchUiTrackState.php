<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

/**
 * Состояние дорожки в UI: активна, скрыта резолвером или «в реестре нет применимых шагов».
 */
enum SetupLaunchUiTrackState: string
{
    case Active = 'active';
    case Suppressed = 'suppressed';
    case InactiveByScope = 'inactive_by_scope';
}
