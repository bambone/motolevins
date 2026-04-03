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
