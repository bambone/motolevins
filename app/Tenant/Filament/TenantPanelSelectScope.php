<?php

declare(strict_types=1);

namespace App\Tenant\Filament;

use Illuminate\Database\Eloquent\Builder;

/**
 * Единообразное ограничение Filament-селектов по колонке владения тенантом.
 * Не вызывает currentTenant() — tenantId передаётся с вызывающей стороны.
 */
final class TenantPanelSelectScope
{
    public static function applyTenantOwnedScope(
        Builder $query,
        ?int $tenantId,
        string $tenantColumn = 'tenant_id',
    ): void {
        if ($tenantId === null) {
            $query->whereRaw('0 = 1');

            return;
        }

        $query->where($tenantColumn, $tenantId);
    }
}
