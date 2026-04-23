<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RedirectMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Machine webhooks: never apply DB tenant redirects (POST must not 30x). Prefix: helpers + ResolveTenantFromDomain.
        if (is_request_under_machine_webhook_path_prefix($request)) {
            return $next($request);
        }

        if (! \currentTenant()) {
            return $next($request);
        }

        $path = '/'.$request->path();
        if ($path === '//') {
            $path = '/';
        }

        $redirect = Redirect::where('from_url', $path)
            ->where('is_active', true)
            ->first();

        if ($redirect) {
            $toUrl = $redirect->to_url;
            if (! Str::startsWith($toUrl, ['http://', 'https://'])) {
                $toUrl = url($toUrl);
            }

            return redirect($toUrl, $redirect->http_code);
        }

        return $next($request);
    }
}
