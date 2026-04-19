<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Enums\MotorcycleLocationMode;
use App\Models\Motorcycle;
use App\Models\RentalUnit;
use App\Models\TenantLocation;
use Illuminate\Database\Eloquent\Builder;

/**
 * Публичная видимость мотоциклов и отбор единиц парка с учётом выбранной локации.
 *
 * Если локация не выбрана — фильтр не применяется (вызывающий код не вызывает эти методы).
 */
final class MotorcycleLocationCatalogService
{
    /**
     * Ограничить запрос мотоциклов тенанта видимостью в указанной локации.
     */
    public function scopeMotorcyclesVisibleAtLocation(Builder $query, TenantLocation $location): Builder
    {
        $locationId = $location->id;

        return $query->where(function (Builder $outer) use ($locationId): void {
            $outer->where('location_mode', MotorcycleLocationMode::Everywhere->value)
                ->orWhere(function (Builder $selected) use ($locationId): void {
                    $selected->where('location_mode', MotorcycleLocationMode::Selected->value)
                        ->whereHas('tenantLocations', fn (Builder $lq) => $lq->whereKey($locationId));
                })
                ->orWhere(function (Builder $perUnit) use ($locationId): void {
                    $perUnit->where('location_mode', MotorcycleLocationMode::PerUnit->value)
                        ->where('uses_fleet_units', true)
                        ->whereHas('rentalUnits', function (Builder $rq) use ($locationId): void {
                            $rq->where('status', 'active')
                                ->whereHas('tenantLocations', fn (Builder $lq) => $lq->whereKey($locationId));
                        });
                });
        });
    }

    /**
     * Доступен ли мотоцикл в локации (при выбранной на сайте локации).
     */
    public function isMotorcycleVisibleAtLocation(Motorcycle $motorcycle, TenantLocation $location): bool
    {
        $mode = $motorcycle->location_mode ?? MotorcycleLocationMode::Everywhere;
        if ($mode === MotorcycleLocationMode::Everywhere) {
            return true;
        }
        if ($mode === MotorcycleLocationMode::Selected) {
            return $motorcycle->tenantLocations()->whereKey($location->id)->exists();
        }
        if (! $motorcycle->uses_fleet_units || $mode !== MotorcycleLocationMode::PerUnit) {
            return false;
        }

        return $motorcycle->rentalUnits()
            ->where('status', 'active')
            ->whereHas('tenantLocations', fn (Builder $lq) => $lq->whereKey($location->id))
            ->exists();
    }

    /**
     * Активные единицы парка с учётом локации для бронирования / календаря.
     *
     * @return Builder<RentalUnit>
     */
    public function rentalUnitsQueryForPublic(Motorcycle $motorcycle, ?TenantLocation $location): Builder
    {
        $q = RentalUnit::query()
            ->where('motorcycle_id', $motorcycle->id)
            ->where('status', 'active');

        if ($location === null) {
            return $q;
        }

        $mode = $motorcycle->location_mode ?? MotorcycleLocationMode::Everywhere;
        if ($mode === MotorcycleLocationMode::Everywhere) {
            return $q;
        }
        if ($mode === MotorcycleLocationMode::Selected) {
            $locationIds = $motorcycle->tenantLocations()->pluck('tenant_locations.id');
            if ($locationIds->isEmpty()) {
                return $q->whereRaw('1 = 0');
            }
            if (! $locationIds->contains($location->id)) {
                return $q->whereRaw('1 = 0');
            }

            return $q;
        }
        // PerUnit
        if (! $motorcycle->uses_fleet_units) {
            return $q->whereRaw('1 = 0');
        }

        return $q->whereHas('tenantLocations', fn (Builder $lq) => $lq->whereKey($location->id));
    }

    /**
     * Единица парка допустима для публичного бронирования (активна + тот же набор, что и {@see rentalUnitsQueryForPublic}).
     */
    public function rentalUnitIsEligibleForPublic(Motorcycle $motorcycle, RentalUnit $unit, ?TenantLocation $location): bool
    {
        if ((int) $unit->motorcycle_id !== (int) $motorcycle->id) {
            return false;
        }
        if ($unit->status !== 'active') {
            return false;
        }

        return $this->rentalUnitsQueryForPublic($motorcycle, $location)->whereKey($unit->id)->exists();
    }
}
