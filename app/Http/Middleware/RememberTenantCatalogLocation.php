<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Catalog\TenantPublicCatalogLocationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RememberTenantCatalogLocation
{
    public function __construct(
        private readonly TenantPublicCatalogLocationService $catalogLocation,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (currentTenant() !== null) {
            $this->catalogLocation->rememberFromRequestIfValid($request);
        }

        return $next($request);
    }
}
