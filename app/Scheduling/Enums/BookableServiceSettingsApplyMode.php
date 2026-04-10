<?php

declare(strict_types=1);

namespace App\Scheduling\Enums;

enum BookableServiceSettingsApplyMode: string
{
    case Replace = 'replace';

    /** Heuristic: only overwrite attributes that still match schema defaults (see mapper). */
    case FillMissingOnly = 'fill_missing_only';
}
