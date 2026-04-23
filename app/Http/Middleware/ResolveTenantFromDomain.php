<?php

namespace App\Http\Middleware;

use App\Tenant\CurrentTenant;
use App\Tenant\TenantResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantFromDomain
{
    public function __construct(
        protected TenantResolver $resolver
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $current = $this->resolver->resolve($request->getHost());

        app()->instance(CurrentTenant::class, $current);
        $request->attributes->set('tenant', $current->tenant);

        if (! $current->isNonTenantHost && ! $current->hasTenant()) {
            if (is_request_under_machine_webhook_path_prefix($request)) {
                return $next($request);
            }

            return response()->view('errors.domain-not-connected', [], 404);
        }

        return $next($request);
    }
}
