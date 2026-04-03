<?php

namespace App\Bookings\Calendar;

use App\Models\Booking;
use App\Models\CrmRequest;
use App\Models\Motorcycle;
use App\Models\RentalUnit;
use App\Models\Tenant;

/**
 * Normalized filters for calendar query (Livewire + query string).
 *
 * @phpstan-type StatusFilter list<string>
 */
final readonly class BookingCalendarFiltersData
{
    /**
     * @param  list<string>|null  $statusValues  Subset of {@see Booking::occupyingStatusValues()} or null = all occupying
     */
    public function __construct(
        public int $tenantId,
        public string $viewType,
        public string $anchorDateYmd,
        public ?int $rentalUnitId,
        public ?int $motorcycleId,
        public ?int $categoryId,
        public ?array $statusValues,
        public ?int $highlightBookingId,
        public ?int $crmRequestPrefilterId,
    ) {}

    /**
     * Build validated DTO: drops unknown IDs, clamps statuses to occupying set.
     *
     * @param  list<string>|null  $statusValues
     */
    public static function make(
        Tenant $tenant,
        string $viewType,
        string $anchorDateYmd,
        ?int $rentalUnitId,
        ?int $motorcycleId,
        ?int $categoryId,
        ?array $statusValues,
        ?int $highlightBookingId,
        ?int $crmRequestPrefilterId,
    ): self {
        $viewType = in_array($viewType, ['month', 'week'], true) ? $viewType : 'month';

        if ($rentalUnitId !== null) {
            $ok = RentalUnit::query()
                ->where('tenant_id', $tenant->id)
                ->whereKey($rentalUnitId)
                ->exists();
            if (! $ok) {
                $rentalUnitId = null;
            }
        }

        if ($motorcycleId !== null) {
            $ok = Motorcycle::query()
                ->where('tenant_id', $tenant->id)
                ->whereKey($motorcycleId)
                ->exists();
            if (! $ok) {
                $motorcycleId = null;
            }
        }

        if ($categoryId !== null) {
            $ok = Motorcycle::query()
                ->where('tenant_id', $tenant->id)
                ->where('category_id', $categoryId)
                ->exists();
            if (! $ok) {
                $categoryId = null;
            }
        }

        $occupying = Booking::occupyingStatusValues();
        $statusFiltered = null;
        if ($statusValues !== null && $statusValues !== []) {
            $statusFiltered = array_values(array_intersect($occupying, $statusValues));
            if ($statusFiltered === []) {
                $statusFiltered = null;
            }
        }

        if ($highlightBookingId !== null) {
            $ok = Booking::query()
                ->where('tenant_id', $tenant->id)
                ->whereKey($highlightBookingId)
                ->exists();
            if (! $ok) {
                $highlightBookingId = null;
            }
        }

        if ($crmRequestPrefilterId !== null) {
            $ok = CrmRequest::query()
                ->where('tenant_id', $tenant->id)
                ->whereKey($crmRequestPrefilterId)
                ->exists();
            if (! $ok) {
                $crmRequestPrefilterId = null;
            }
        }

        return new self(
            tenantId: (int) $tenant->id,
            viewType: $viewType,
            anchorDateYmd: $anchorDateYmd,
            rentalUnitId: $rentalUnitId,
            motorcycleId: $motorcycleId,
            categoryId: $categoryId,
            statusValues: $statusFiltered,
            highlightBookingId: $highlightBookingId,
            crmRequestPrefilterId: $crmRequestPrefilterId,
        );
    }

    /**
     * @return list<string>
     */
    public function effectiveStatusValues(): array
    {
        return $this->statusValues ?? Booking::occupyingStatusValues();
    }
}
