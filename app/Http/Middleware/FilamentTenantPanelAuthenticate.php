<?php

namespace App\Http\Middleware;

use App\Http\Responses\FilamentAccessDeniedRedirect;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Как стандартный Filament Authenticate, но вместо 403 при «сессия есть, но панель недоступна»
 * (например, сотрудник платформы на домене тенанта) — редирект на страницу входа с пояснением.
 */
class FilamentTenantPanelAuthenticate extends FilamentAuthenticate
{
    protected function authenticate($request, array $guards): void
    {
        $guard = Filament::auth();

        if (! $guard->check()) {
            $this->unauthenticated($request, $guards);

            return;
        }

        $this->auth->shouldUse(Filament::getAuthGuard());

        /** @var Model $user */
        $user = $guard->user();

        $panel = Filament::getCurrentOrDefaultPanel();

        $denied = $user instanceof FilamentUser
            ? (! $user->canAccessPanel($panel))
            : (config('app.env') !== 'local');

        if (! $denied) {
            return;
        }

        $platformHost = trim((string) config('app.platform_host', ''));
        $platformHint = $platformHost !== ''
            ? "Откройте консоль платформы: https://{$platformHost}/login"
            : 'Откройте консоль платформы с настроенного PLATFORM_HOST.';

        throw new HttpResponseException(
            redirect()->to($panel->getLoginUrl())->with(
                FilamentAccessDeniedRedirect::SESSION_KEY,
                "Для кабинета клиента на этом домене нужна учётная запись команды этого клиента. Войдите под соответствующим email или {$platformHint}"
            )
        );
    }
}
