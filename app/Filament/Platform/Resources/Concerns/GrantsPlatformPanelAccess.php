<?php

namespace App\Filament\Platform\Resources\Concerns;

use App\Auth\AccessRoles;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

trait GrantsPlatformPanelAccess
{
    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->hasAnyRole(AccessRoles::platformRoles());
    }
}
