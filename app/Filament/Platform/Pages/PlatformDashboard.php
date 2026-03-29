<?php

namespace App\Filament\Platform\Pages;

use Filament\Pages\Dashboard;

/**
 * Дашборд на /dashboard: корень PLATFORM_HOST редиректит сюда из routes/web.php.
 */
class PlatformDashboard extends Dashboard
{
    protected static string $routePath = '/dashboard';
}
