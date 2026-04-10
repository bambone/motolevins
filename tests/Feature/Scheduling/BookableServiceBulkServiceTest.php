<?php

declare(strict_types=1);

namespace Tests\Feature\Scheduling;

use App\Models\BookableService;
use App\Models\BookingSettingsPreset;
use App\Models\Motorcycle;
use App\Models\Tenant;
use App\Scheduling\BookableServiceBulkService;
use App\Scheduling\Enums\BookableServiceSettingsApplyMode;
use App\Scheduling\Enums\SchedulingScope;
use App\Services\CurrentTenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\Support\SchedulingTestScenarios;
use Tests\TestCase;

final class BookableServiceBulkServiceTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;
    use SchedulingTestScenarios;

    private function bulk(): BookableServiceBulkService
    {
        return app(BookableServiceBulkService::class);
    }

    private function makeMotorcycle(Tenant $tenant, string $slug = 'm-bulk'): Motorcycle
    {
        return Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Bulk Moto',
            'slug' => $slug,
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 1000,
        ]);
    }

    private function makePreset(Tenant $tenant, array $payloadOverrides = []): BookingSettingsPreset
    {
        return BookingSettingsPreset::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Standard',
            'description' => null,
            'payload' => array_merge([
                'duration_minutes' => 90,
                'slot_step_minutes' => 20,
                'buffer_before_minutes' => 5,
                'buffer_after_minutes' => 5,
                'min_booking_notice_minutes' => 180,
                'max_booking_horizon_days' => 45,
                'requires_confirmation' => false,
                'sort_weight' => 3,
                'sync_title_from_source' => true,
            ], $payloadOverrides),
        ]);
    }

    public function test_apply_preset_to_motorcycle_creates_linked_service_when_missing(): void
    {
        $tenant = $this->createTenantWithActiveDomain('bulk_moto_create');
        app(CurrentTenantManager::class)->setTenant($tenant);
        $m = $this->makeMotorcycle($tenant);
        $preset = $this->makePreset($tenant);

        $svc = $this->bulk()->applyPresetToMotorcycle($m, $preset, true, BookableServiceSettingsApplyMode::Replace, true);

        $this->assertSame($tenant->id, $svc->tenant_id);
        $this->assertSame($m->id, $svc->motorcycle_id);
        $this->assertTrue($svc->is_active);
        $this->assertSame(90, $svc->duration_minutes);
        $this->assertTrue($svc->schedulingTarget?->scheduling_enabled ?? false);
    }

    public function test_preset_tenant_mismatch_throws(): void
    {
        $tA = $this->createTenantWithActiveDomain('bulk_ta');
        $tB = $this->createTenantWithActiveDomain('bulk_tb');
        app(CurrentTenantManager::class)->setTenant($tA);
        $preset = $this->makePreset($tB);

        $svc = $this->schedulingCreateBookableService($tA, ['slug' => 's1']);

        $this->expectException(\InvalidArgumentException::class);
        $this->bulk()->applyPresetToService($svc, $preset, false);
    }

    public function test_service_tenant_mismatch_throws(): void
    {
        $tA = $this->createTenantWithActiveDomain('bulk_svc_a');
        $tB = $this->createTenantWithActiveDomain('bulk_svc_b');
        app(CurrentTenantManager::class)->setTenant($tA);
        $preset = $this->makePreset($tA);
        $svc = $this->schedulingCreateBookableService($tB, ['slug' => 'other']);

        $this->expectException(\InvalidArgumentException::class);
        $this->bulk()->applyPresetToService($svc, $preset, false);
    }

    public function test_apply_preset_to_service_updates_and_optionally_enables(): void
    {
        $tenant = $this->createTenantWithActiveDomain('bulk_svc_upd');
        app(CurrentTenantManager::class)->setTenant($tenant);
        $preset = $this->makePreset($tenant);
        $svc = $this->schedulingCreateBookableService($tenant, [
            'slug' => 'upd',
            'is_active' => false,
        ]);
        $svc->schedulingTarget?->update(['scheduling_enabled' => false]);

        $this->bulk()->applyPresetToService($svc, $preset, true, BookableServiceSettingsApplyMode::Replace);

        $svc->refresh();
        $svc->unsetRelation('schedulingTarget');
        $this->assertSame(90, $svc->duration_minutes);
        $this->assertTrue($svc->is_active);
        $this->assertTrue($svc->schedulingTarget?->scheduling_enabled ?? false);
    }

    public function test_enable_online_booking_for_motorcycles_without_preset_uses_ensure_and_enable(): void
    {
        $tenant = $this->createTenantWithActiveDomain('bulk_moto_en');
        app(CurrentTenantManager::class)->setTenant($tenant);
        $m = $this->makeMotorcycle($tenant);

        $this->bulk()->enableOnlineBookingForMotorcycles([$m], null);

        $svc = BookableService::query()
            ->where('tenant_id', $tenant->id)
            ->where('motorcycle_id', $m->id)
            ->first();
        $this->assertNotNull($svc);
        $this->assertTrue($svc->is_active);
        $this->assertTrue($svc->schedulingTarget?->scheduling_enabled ?? false);
        $this->assertEquals(SchedulingScope::Tenant, $svc->scheduling_scope);
    }
}
