<?php

namespace App\Http\Middleware;

use App\Auth\AccessRoles;
use App\Models\TenantDomain;
use App\Tenant\HostClassifier;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformAccess
{
    public function __construct(
        protected HostClassifier $hostClassifier
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $host = TenantDomain::normalizeHost($request->getHost());

        if (! $this->hostClassifier->isPlatformPanelHost($host)) {
            abort(403, 'Platform Console доступна только с platform host.');
        }

        $user = $request->user();

        if ($user !== null && ! $user->hasAnyRole(AccessRoles::platformRoles())) {
            abort(403, 'Недостаточно прав для Platform Console.');
        }

        return $next($request);
    }
}
