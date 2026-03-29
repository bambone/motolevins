<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Use the incoming request scheme + host as the URL generator root so absolute
 * URLs (e.g. Livewire update URI) stay same-origin when APP_URL targets another
 * host (apex marketing vs platform subdomain).
 */
class UseRequestOriginForUrls
{
    public function handle(Request $request, Closure $next): Response
    {
        URL::useOrigin($request->getSchemeAndHttpHost());

        return $next($request);
    }
}
