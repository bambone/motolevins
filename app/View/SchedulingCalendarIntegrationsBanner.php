<?php

declare(strict_types=1);

namespace App\View;

use App\Models\Tenant;
use Filament\Facades\Filament;

/**
 * Баннер «календарные интеграции выключены» только в контексте экранов записи/календаря,
 * чтобы не пугать на остальных разделах админки.
 */
final class SchedulingCalendarIntegrationsBanner
{
    /**
     * @return list<string> path-префиксы без ведущего слэша (например admin/bookable-services)
     */
    public static function filamentPathPrefixes(): array
    {
        $panel = trim((string) (Filament::getCurrentPanel()?->getPath() ?? 'admin'), '/');

        return [
            $panel.'/bookable-services',
            $panel.'/calendar-connections',
            $panel.'/calendar-occupancy-mappings',
            $panel.'/availability-rules',
            $panel.'/availability-exceptions',
            $panel.'/manual-busy-blocks',
            $panel.'/scheduling-resources',
            $panel.'/scheduling-targets',
            $panel.'/scheduling/',
        ];
    }

    public static function shouldShow(?Tenant $tenant, string $requestPath): bool
    {
        if ($tenant === null || ! $tenant->scheduling_module_enabled || $tenant->calendar_integrations_enabled) {
            return false;
        }

        foreach (self::filamentPathPrefixes() as $prefix) {
            if ($requestPath === $prefix || str_starts_with($requestPath, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
