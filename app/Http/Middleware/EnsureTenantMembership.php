<?php

namespace App\Http\Middleware;

use App\Auth\AccessRoles;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantMembership
{
    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()?->getName();
        if (is_string($routeName) && str_starts_with($routeName, 'filament.admin.auth.')) {
            return $next($request);
        }

        $tenant = currentTenant();
        $user = $request->user();

        if (! $tenant || ! $user) {
            return $next($request);
        }

        $membership = $user->tenants()->where('tenant_id', $tenant->id)->first();

        if ($membership === null) {
            abort(403, 'У вас нет доступа к этому клиенту.');
        }

        if ($membership->pivot->status !== 'active') {
            abort(403, 'Участие в этом клиенте не активно.');
        }

        if (! in_array($membership->pivot->role, AccessRoles::tenantMembershipRolesForPanel(), true)) {
            abort(403, 'Недостаточно прав для этой панели.');
        }

        return $next($request);
    }
}
