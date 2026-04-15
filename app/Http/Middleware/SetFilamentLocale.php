<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Единая локаль для Filament (platform + tenant): строки Laravel, Livewire и пакетов.
 * Приоритет: явная локаль пользователя → локаль клиента (tenant) → config('app.locale').
 */
final class SetFilamentLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);
        $previous = app()->getLocale();
        app()->setLocale($locale);

        try {
            return $next($request);
        } finally {
            app()->setLocale($previous);
        }
    }

    private function resolveLocale(Request $request): string
    {
        $user = $request->user();
        if ($user !== null) {
            $fromUser = data_get($user, 'locale');
            if (is_string($fromUser) && $fromUser !== '') {
                return $fromUser;
            }
        }

        $tenant = currentTenant();
        if ($tenant !== null) {
            $fromTenant = $tenant->locale;
            if (is_string($fromTenant) && $fromTenant !== '') {
                return $fromTenant;
            }
        }

        return (string) config('app.locale', 'ru');
    }
}
