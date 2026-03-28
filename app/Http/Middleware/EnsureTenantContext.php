<?php

namespace App\Http\Middleware;

use App\Models\TenantDomain;
use App\Tenant\CurrentTenant;
use App\Tenant\HostClassifier;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantContext
{
    public function __construct(
        protected HostClassifier $hostClassifier
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $current = app(CurrentTenant::class);

        if ($current->hasTenant()) {
            return $next($request);
        }

        $host = TenantDomain::normalizeHost($request->getHost());

        if ($this->hostClassifier->isNonTenantHost($host)) {
            abort(404, 'Tenant not found');
        }

        return response()->view('errors.domain-not-connected', [], 404);
    }
}
