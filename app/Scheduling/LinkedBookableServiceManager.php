<?php

declare(strict_types=1);

namespace App\Scheduling;

use App\Models\BookableService;
use App\Models\Motorcycle;
use App\Models\RentalUnit;
use App\Models\SchedulingTarget;
use App\Scheduling\Enums\CalendarUsageMode;
use App\Scheduling\Enums\OccupancyScopeMode;
use App\Scheduling\Enums\SchedulingScope;
use App\Scheduling\Enums\SchedulingTargetType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class LinkedBookableServiceManager
{
    public function findLinkedForMotorcycle(Motorcycle $motorcycle, SchedulingScope $scope): ?BookableService
    {
        if ($motorcycle->tenant_id === null) {
            return null;
        }

        return BookableService::query()
            ->where('scheduling_scope', $scope)
            ->where('tenant_id', $motorcycle->tenant_id)
            ->where('motorcycle_id', $motorcycle->id)
            ->whereNull('rental_unit_id')
            ->first();
    }

    public function findLinkedForRentalUnit(RentalUnit $rentalUnit, SchedulingScope $scope): ?BookableService
    {
        return BookableService::query()
            ->where('scheduling_scope', $scope)
            ->where('tenant_id', $rentalUnit->tenant_id)
            ->where('rental_unit_id', $rentalUnit->id)
            ->whereNull('motorcycle_id')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function applyMotorcycleLinkedForm(Motorcycle $motorcycle, SchedulingScope $scope, array $payload): void
    {
        if ($motorcycle->tenant_id === null) {
            return;
        }

        DB::transaction(function () use ($motorcycle, $scope, $payload): void {
            $enabled = (bool) ($payload['linked_booking_enabled'] ?? false);
            $existing = $this->findLinkedForMotorcycle($motorcycle, $scope);

            if (! $enabled) {
                if ($existing !== null) {
                    $this->disableLinkedService($existing);
                }

                return;
            }

            $service = $existing ?? $this->createLinkedForMotorcycle($motorcycle, $scope, $payload);
            $this->assertIntegrity($service);
            $this->applyPayloadToService($service, $payload);
            $this->syncTitleFromMotorcycleIfNeeded($service, $motorcycle);
            $service->is_active = true;
            $service->save();
            $this->ensureSchedulingTarget($service);
            $this->activateSchedulingTarget($service);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function applyRentalUnitLinkedForm(RentalUnit $rentalUnit, SchedulingScope $scope, array $payload): void
    {
        DB::transaction(function () use ($rentalUnit, $scope, $payload): void {
            $enabled = (bool) ($payload['linked_booking_enabled'] ?? false);
            $existing = $this->findLinkedForRentalUnit($rentalUnit, $scope);

            if (! $enabled) {
                if ($existing !== null) {
                    $this->disableLinkedService($existing);
                }

                return;
            }

            $service = $existing ?? $this->createLinkedForRentalUnit($rentalUnit, $scope, $payload);
            $this->assertIntegrity($service);
            $this->applyPayloadToService($service, $payload);
            $this->syncTitleFromRentalUnitIfNeeded($service, $rentalUnit);
            $service->is_active = true;
            $service->save();
            $this->ensureSchedulingTarget($service);
            $this->activateSchedulingTarget($service);
        });
    }

    public function syncLinkedBookableFromMotorcycle(Motorcycle $motorcycle): void
    {
        if ($motorcycle->tenant_id === null) {
            return;
        }

        $service = $this->findLinkedForMotorcycle($motorcycle, SchedulingScope::Tenant);
        if ($service === null || ! $service->sync_title_from_source) {
            return;
        }

        DB::transaction(function () use ($service, $motorcycle): void {
            $service->title = (string) $motorcycle->name;
            $service->save();
            $this->syncTargetLabel($service, $service->title);
        });
    }

    public function syncLinkedBookableFromRentalUnit(RentalUnit $rentalUnit): void
    {
        $service = $this->findLinkedForRentalUnit($rentalUnit, SchedulingScope::Tenant);
        if ($service === null || ! $service->sync_title_from_source) {
            return;
        }

        DB::transaction(function () use ($service, $rentalUnit): void {
            $label = RentalUnitSchedulingLabel::label($rentalUnit);
            $service->title = $label;
            $service->save();
            $this->syncTargetLabel($service, $label);
        });
    }

    public function assertIntegrity(BookableService $service): void
    {
        $mId = $service->motorcycle_id;
        $rId = $service->rental_unit_id;

        if ($mId !== null && $rId !== null) {
            throw new BookableServiceIntegrityException('BookableService cannot have both motorcycle_id and rental_unit_id.');
        }

        if ($mId !== null) {
            $bike = Motorcycle::query()->find($mId);
            if ($bike === null || (int) $bike->tenant_id !== (int) $service->tenant_id) {
                throw new BookableServiceIntegrityException('Motorcycle source must belong to the same tenant as BookableService.');
            }
        }

        if ($rId !== null) {
            $unit = RentalUnit::query()->find($rId);
            if ($unit === null || (int) $unit->tenant_id !== (int) $service->tenant_id) {
                throw new BookableServiceIntegrityException('RentalUnit source must belong to the same tenant as BookableService.');
            }
        }
    }

    public function disableLinkedService(BookableService $service): void
    {
        DB::transaction(function () use ($service): void {
            $service->is_active = false;
            $service->save();
            $target = $service->schedulingTarget;
            if ($target !== null) {
                $target->scheduling_enabled = false;
                $target->save();
            }
        });
    }

    public function disableOnlineBookingForService(BookableService $service): void
    {
        $this->disableLinkedService($service);
    }

    public function enableOnlineBookingForService(BookableService $service): void
    {
        DB::transaction(function () use ($service): void {
            $this->assertIntegrity($service);
            $service->is_active = true;
            $service->save();
            $this->ensureSchedulingTarget($service);
            $this->activateSchedulingTarget($service);
        });
    }

    /**
     * Creates a tenant-scoped linked BookableService for the motorcycle when missing.
     * Does not enable online booking or change SchedulingTarget.scheduling_enabled.
     *
     * @param  array<string, mixed>  $payload  linked_* keys, merged over defaults
     */
    public function ensureLinkedServiceForMotorcycle(Motorcycle $motorcycle, SchedulingScope $scope, array $payload = []): BookableService
    {
        if ($motorcycle->tenant_id === null) {
            throw new BookableServiceIntegrityException('Motorcycle has no tenant_id.');
        }

        $existing = $this->findLinkedForMotorcycle($motorcycle, $scope);
        if ($existing !== null) {
            return $existing;
        }

        $defaults = [
            'linked_sync_title_from_source' => true,
            'linked_duration_minutes' => 60,
            'linked_slot_step_minutes' => 15,
            'linked_buffer_before_minutes' => 0,
            'linked_buffer_after_minutes' => 0,
            'linked_min_booking_notice_minutes' => 120,
            'linked_max_booking_horizon_days' => 60,
            'linked_requires_confirmation' => true,
            'linked_sort_weight' => 0,
        ];

        return DB::transaction(fn (): BookableService => $this->createLinkedForMotorcycle(
            $motorcycle,
            $scope,
            array_merge($defaults, $payload),
        ));
    }

    /**
     * Applies whitelisted scheduling fields (canonical BookableService attribute names, normalized types).
     * Does not change is_active or scheduling target enabled flags.
     *
     * @param  array<string, mixed>  $canonicalNormalized  e.g. duration_minutes => int
     */
    public function applySchedulingSettingsToService(BookableService $service, array $canonicalNormalized): void
    {
        if ($canonicalNormalized === []) {
            return;
        }

        $mapper = app(BookableServiceSettingsMapper::class);
        $linked = $mapper->toLinkedPayload($canonicalNormalized);

        DB::transaction(function () use ($service, $linked): void {
            $this->assertIntegrity($service);
            $this->applyPayloadToService($service, $linked);

            if ($service->motorcycle_id !== null) {
                $bike = Motorcycle::query()->find($service->motorcycle_id);
                if ($bike !== null) {
                    $this->syncTitleFromMotorcycleIfNeeded($service, $bike);
                }
            } elseif ($service->rental_unit_id !== null) {
                $unit = RentalUnit::query()->find($service->rental_unit_id);
                if ($unit !== null) {
                    $this->syncTitleFromRentalUnitIfNeeded($service, $unit);
                }
            }

            $service->save();
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function createLinkedForMotorcycle(Motorcycle $motorcycle, SchedulingScope $scope, array $payload): BookableService
    {
        $existing = $this->findLinkedForMotorcycle($motorcycle, $scope);
        if ($existing !== null) {
            return $existing;
        }

        $syncTitle = (bool) ($payload['linked_sync_title_from_source'] ?? true);
        $title = $syncTitle ? (string) $motorcycle->name : 'Запись';

        $slug = $this->uniqueSlug((int) $motorcycle->tenant_id, $scope, Str::slug($motorcycle->slug.'-zapis'));

        return BookableService::query()->create([
            'scheduling_scope' => $scope,
            'tenant_id' => $motorcycle->tenant_id,
            'motorcycle_id' => $motorcycle->id,
            'rental_unit_id' => null,
            'slug' => $slug,
            'title' => $title,
            'description' => null,
            'duration_minutes' => (int) ($payload['linked_duration_minutes'] ?? 60),
            'slot_step_minutes' => (int) ($payload['linked_slot_step_minutes'] ?? 15),
            'buffer_before_minutes' => (int) ($payload['linked_buffer_before_minutes'] ?? 0),
            'buffer_after_minutes' => (int) ($payload['linked_buffer_after_minutes'] ?? 0),
            'min_booking_notice_minutes' => (int) ($payload['linked_min_booking_notice_minutes'] ?? 120),
            'max_booking_horizon_days' => (int) ($payload['linked_max_booking_horizon_days'] ?? 60),
            'requires_confirmation' => (bool) ($payload['linked_requires_confirmation'] ?? true),
            'is_active' => false,
            'sort_weight' => (int) ($payload['linked_sort_weight'] ?? 0),
            'sync_title_from_source' => $syncTitle,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function createLinkedForRentalUnit(RentalUnit $rentalUnit, SchedulingScope $scope, array $payload): BookableService
    {
        $existing = $this->findLinkedForRentalUnit($rentalUnit, $scope);
        if ($existing !== null) {
            return $existing;
        }

        $syncTitle = (bool) ($payload['linked_sync_title_from_source'] ?? true);
        $title = $syncTitle ? RentalUnitSchedulingLabel::label($rentalUnit) : 'Запись';

        $slug = $this->uniqueSlug((int) $rentalUnit->tenant_id, $scope, 'unit-'.$rentalUnit->id.'-zapis');

        return BookableService::query()->create([
            'scheduling_scope' => $scope,
            'tenant_id' => $rentalUnit->tenant_id,
            'motorcycle_id' => null,
            'rental_unit_id' => $rentalUnit->id,
            'slug' => $slug,
            'title' => $title,
            'description' => null,
            'duration_minutes' => (int) ($payload['linked_duration_minutes'] ?? 60),
            'slot_step_minutes' => (int) ($payload['linked_slot_step_minutes'] ?? 15),
            'buffer_before_minutes' => (int) ($payload['linked_buffer_before_minutes'] ?? 0),
            'buffer_after_minutes' => (int) ($payload['linked_buffer_after_minutes'] ?? 0),
            'min_booking_notice_minutes' => (int) ($payload['linked_min_booking_notice_minutes'] ?? 120),
            'max_booking_horizon_days' => (int) ($payload['linked_max_booking_horizon_days'] ?? 60),
            'requires_confirmation' => (bool) ($payload['linked_requires_confirmation'] ?? true),
            'is_active' => false,
            'sort_weight' => (int) ($payload['linked_sort_weight'] ?? 0),
            'sync_title_from_source' => $syncTitle,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function applyPayloadToService(BookableService $service, array $payload): void
    {
        if (array_key_exists('linked_sync_title_from_source', $payload)) {
            $service->sync_title_from_source = (bool) $payload['linked_sync_title_from_source'];
        }
        if (array_key_exists('linked_duration_minutes', $payload)) {
            $service->duration_minutes = (int) $payload['linked_duration_minutes'];
        }
        if (array_key_exists('linked_slot_step_minutes', $payload)) {
            $service->slot_step_minutes = (int) $payload['linked_slot_step_minutes'];
        }
        if (array_key_exists('linked_buffer_before_minutes', $payload)) {
            $service->buffer_before_minutes = (int) $payload['linked_buffer_before_minutes'];
        }
        if (array_key_exists('linked_buffer_after_minutes', $payload)) {
            $service->buffer_after_minutes = (int) $payload['linked_buffer_after_minutes'];
        }
        if (array_key_exists('linked_min_booking_notice_minutes', $payload)) {
            $service->min_booking_notice_minutes = (int) $payload['linked_min_booking_notice_minutes'];
        }
        if (array_key_exists('linked_max_booking_horizon_days', $payload)) {
            $service->max_booking_horizon_days = (int) $payload['linked_max_booking_horizon_days'];
        }
        if (array_key_exists('linked_requires_confirmation', $payload)) {
            $service->requires_confirmation = (bool) $payload['linked_requires_confirmation'];
        }
        if (array_key_exists('linked_sort_weight', $payload)) {
            $service->sort_weight = (int) $payload['linked_sort_weight'];
        }
    }

    private function syncTitleFromMotorcycleIfNeeded(BookableService $service, Motorcycle $motorcycle): void
    {
        if (! $service->sync_title_from_source) {
            return;
        }
        $service->title = (string) $motorcycle->name;
        $this->syncTargetLabel($service, $service->title);
    }

    private function syncTitleFromRentalUnitIfNeeded(BookableService $service, RentalUnit $rentalUnit): void
    {
        if (! $service->sync_title_from_source) {
            return;
        }
        $label = RentalUnitSchedulingLabel::label($rentalUnit);
        $service->title = $label;
        $this->syncTargetLabel($service, $label);
    }

    private function syncTargetLabel(BookableService $service, string $label): void
    {
        $target = $service->schedulingTarget;
        if ($target === null) {
            return;
        }
        $target->label = $label;
        $target->save();
    }

    private function ensureSchedulingTarget(BookableService $service): void
    {
        SchedulingTarget::query()->firstOrCreate(
            [
                'scheduling_scope' => $service->scheduling_scope,
                'tenant_id' => $service->tenant_id,
                'target_type' => SchedulingTargetType::BookableService,
                'target_id' => $service->id,
            ],
            [
                'label' => $service->title,
                'scheduling_enabled' => false,
                'external_busy_enabled' => false,
                'internal_busy_enabled' => true,
                'auto_write_to_calendar_enabled' => false,
                'occupancy_scope_mode' => OccupancyScopeMode::Generic,
                'calendar_usage_mode' => CalendarUsageMode::Disabled,
                'is_active' => true,
            ]
        );
    }

    private function activateSchedulingTarget(BookableService $service): void
    {
        $target = $service->fresh()->schedulingTarget;
        if ($target === null) {
            return;
        }
        $target->scheduling_enabled = true;
        $target->is_active = true;
        $target->label = $service->title;
        $target->save();
    }

    private function uniqueSlug(int $tenantId, SchedulingScope $scope, string $base): string
    {
        $slug = $base !== '' ? $base : 'scheduling';
        $i = 1;
        while (BookableService::query()
            ->where('scheduling_scope', $scope)
            ->where('tenant_id', $tenantId)
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
