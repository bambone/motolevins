<?php

declare(strict_types=1);

namespace App\Scheduling;

use App\Models\BookableService;
use App\Models\BookingSettingsPreset;
use App\Models\Motorcycle;
use App\Scheduling\Enums\BookableServiceSettingsApplyMode;
use App\Scheduling\Enums\SchedulingScope;
use Illuminate\Support\Facades\DB;

/**
 * Orchestrates bulk scheduling scenarios; domain rules live in LinkedBookableServiceManager.
 */
final class BookableServiceBulkService
{
    public function __construct(
        private readonly LinkedBookableServiceManager $manager,
        private readonly BookableServiceSettingsMapper $mapper,
    ) {}

    public function applyPresetToService(
        BookableService $service,
        BookingSettingsPreset $preset,
        bool $enableOnlineBooking = false,
        BookableServiceSettingsApplyMode $mode = BookableServiceSettingsApplyMode::Replace,
    ): void {
        $this->assertPresetMatchesContext($preset);
        $this->assertServiceMatchesContext($service);

        $raw = $this->mapper->extractWhitelisted($preset->payload ?? []);
        $filtered = $this->mapper->filterForApplyMode($service, $raw, $mode);
        $normalized = $this->mapper->normalizeForBookableService($filtered);

        DB::transaction(function () use ($service, $normalized, $enableOnlineBooking): void {
            if ($normalized !== []) {
                $this->manager->applySchedulingSettingsToService($service->fresh(), $normalized);
            }
            if ($enableOnlineBooking) {
                $this->manager->enableOnlineBookingForService($service->fresh());
            }
        });
    }

    /**
     * @param  bool  $syncTitleFromSource  when true, title follows motorcycle name (linked tab behaviour)
     */
    public function applyPresetToMotorcycle(
        Motorcycle $motorcycle,
        BookingSettingsPreset $preset,
        bool $enableOnlineBooking = true,
        BookableServiceSettingsApplyMode $mode = BookableServiceSettingsApplyMode::Replace,
        bool $syncTitleFromSource = true,
    ): BookableService {
        $this->assertPresetMatchesContext($preset);
        $this->assertMotorcycleMatchesContext($motorcycle);

        $raw = $this->mapper->extractWhitelisted($preset->payload ?? []);
        $existing = $this->manager->findLinkedForMotorcycle($motorcycle, SchedulingScope::Tenant);
        $filtered = $existing !== null
            ? $this->mapper->filterForApplyMode($existing, $raw, $mode)
            : $raw;
        $normalized = $this->mapper->normalizeForBookableService($filtered);
        $linked = $this->mapper->toLinkedPayload($normalized);
        $linked['linked_sync_title_from_source'] = $syncTitleFromSource;
        $linked['linked_booking_enabled'] = $enableOnlineBooking;

        $this->manager->applyMotorcycleLinkedForm($motorcycle, SchedulingScope::Tenant, $linked);

        $service = $this->manager->findLinkedForMotorcycle($motorcycle, SchedulingScope::Tenant);
        if ($service === null) {
            throw new \RuntimeException('Linked bookable service missing after applyMotorcycleLinkedForm.');
        }

        return $service;
    }

    /**
     * @param  iterable<int, BookableService>  $services
     */
    public function enableOnlineBookingForServices(iterable $services): void
    {
        foreach ($services as $service) {
            $this->assertServiceMatchesContext($service);
            $this->manager->enableOnlineBookingForService($service->fresh());
        }
    }

    /**
     * @param  iterable<int, BookableService>  $services
     */
    public function disableOnlineBookingForServices(iterable $services): void
    {
        foreach ($services as $service) {
            $this->assertServiceMatchesContext($service);
            $this->manager->disableOnlineBookingForService($service->fresh());
        }
    }

    /**
     * @param  iterable<int, Motorcycle>  $motorcycles
     */
    public function enableOnlineBookingForMotorcycles(
        iterable $motorcycles,
        ?BookingSettingsPreset $preset = null,
        BookableServiceSettingsApplyMode $mode = BookableServiceSettingsApplyMode::Replace,
        bool $syncTitleFromSource = true,
    ): void {
        foreach ($motorcycles as $motorcycle) {
            $this->assertMotorcycleMatchesContext($motorcycle);
            if ($preset !== null) {
                $this->applyPresetToMotorcycle(
                    $motorcycle,
                    $preset,
                    true,
                    $mode,
                    $syncTitleFromSource,
                );
            } else {
                DB::transaction(function () use ($motorcycle): void {
                    $service = $this->manager->ensureLinkedServiceForMotorcycle($motorcycle, SchedulingScope::Tenant);
                    $this->manager->enableOnlineBookingForService($service->fresh());
                });
            }
        }
    }

    private function assertPresetMatchesContext(BookingSettingsPreset $preset): void
    {
        $tenantId = currentTenant()?->id;
        if ($tenantId === null || (int) $preset->tenant_id !== (int) $tenantId) {
            throw new \InvalidArgumentException('Preset does not belong to the current tenant.');
        }
    }

    private function assertServiceMatchesContext(BookableService $service): void
    {
        $tenantId = currentTenant()?->id;
        if ($tenantId === null || (int) $service->tenant_id !== (int) $tenantId) {
            throw new \InvalidArgumentException('BookableService does not belong to the current tenant.');
        }
    }

    private function assertMotorcycleMatchesContext(Motorcycle $motorcycle): void
    {
        $tenantId = currentTenant()?->id;
        if ($tenantId === null || (int) $motorcycle->tenant_id !== (int) $tenantId) {
            throw new \InvalidArgumentException('Motorcycle does not belong to the current tenant.');
        }
    }
}
