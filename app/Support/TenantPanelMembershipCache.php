<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Один запрос membership tenant_user на HTTP-запрос: Gate::before и политики дергают ability много раз.
 *
 * @return Tenant|null связанный тенант с заполненным pivot (role, status)
 */
final class TenantPanelMembershipCache
{
    public static function membershipFor(Request $request, User $user, Tenant $tenant): ?Tenant
    {
        $key = 'tenant_panel.membership.'.((int) $user->id).'.'.((int) $tenant->id);
        if ($request->attributes->has($key)) {
            /** @var Tenant|null */
            return $request->attributes->get($key);
        }

        $membership = $user->tenants()->where('tenant_id', $tenant->id)->first();
        $request->attributes->set($key, $membership);

        return $membership;
    }
}
