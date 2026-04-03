<?php

namespace App\Bookings\Calendar;

use App\Models\Booking;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

final class BookingCalendarQuery
{
    /**
     * Bookings overlapping the fetch window (tenant scoped, occupying statuses, SQL filters).
     */
    public function baseBuilder(
        BookingCalendarFiltersData $filters,
        CarbonInterface $rangeStart,
        CarbonInterface $rangeEnd,
    ): Builder {
        $tenantId = $filters->tenantId;
        $statuses = $filters->effectiveStatusValues();

        $startDate = $rangeStart->copy()->startOfDay()->toDateString();
        $endDate = $rangeEnd->copy()->startOfDay()->toDateString();

        $query = Booking::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('status', $statuses)
            ->where(function (Builder $q) use ($rangeStart, $rangeEnd, $startDate, $endDate) {
                $q->where(function (Builder $q2) use ($rangeStart, $rangeEnd) {
                    $q2->whereNotNull('start_at')
                        ->whereNotNull('end_at')
                        ->where('start_at', '<', $rangeEnd)
                        ->where('end_at', '>', $rangeStart);
                })->orWhere(function (Builder $q2) use ($startDate, $endDate) {
                    // Даты — как в CRM / availability: пересечение по start_date/end_date,
                    // даже если start_at/end_at заполнены (они могут не совпадать с периодом по дням).
                    $q2->whereNotNull('start_date')
                        ->whereNotNull('end_date')
                        ->whereDate('start_date', '<=', $endDate)
                        ->whereDate('end_date', '>=', $startDate);
                });
            });

        if ($filters->rentalUnitId !== null) {
            $query->where('rental_unit_id', $filters->rentalUnitId);
        }

        if ($filters->motorcycleId !== null) {
            $query->where('motorcycle_id', $filters->motorcycleId);
        }

        if ($filters->categoryId !== null) {
            $query->whereHas('motorcycle', static fn (Builder $mq) => $mq->where('category_id', $filters->categoryId));
        }

        if ($filters->crmRequestPrefilterId !== null) {
            $crmId = $filters->crmRequestPrefilterId;
            $query->whereHas('lead', static fn (Builder $lq) => $lq->where('crm_request_id', $crmId));
        }

        return $query
            ->with([
                'rentalUnit.motorcycle',
                'motorcycle',
                'lead',
                'customer',
            ]);
    }
}
