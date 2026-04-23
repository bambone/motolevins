<?php

declare(strict_types=1);

namespace Tests\Feature\Scheduling;

use App\Models\BookableService;
use App\Models\Motorcycle;
use App\Models\RentalUnit;
use App\Models\SchedulingTarget;
use App\Models\Tenant;
use App\Scheduling\BookableServiceIntegrityException;
use App\Scheduling\Enums\BookableServiceLinkType;
use App\Scheduling\Enums\SchedulingScope;
use App\Scheduling\Enums\SchedulingTargetType;
use App\Scheduling\LinkedBookableServiceManager;
use App\Scheduling\RentalUnitSchedulingLabel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

final class LinkedBookableServiceManagerTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    private function manager(): LinkedBookableServiceManager
    {
        return app(LinkedBookableServiceManager::class);
    }

    private function makeMotorcycle(Tenant $tenant, string $slug = 'moto-a'): Motorcycle
    {
        return Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Test Moto',
            'slug' => $slug,
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 1000,
        ]);
    }

    public function test_enable_motorcycle_creates_single_service_and_target(): void
    {
        $tenant = $this->createTenantWithActiveDomain('link_moto');
        $m = $this->makeMotorcycle($tenant);

        $this->manager()->applyMotorcycleLinkedForm($m, SchedulingScope::Tenant, [
            'linked_booking_enabled' => true,
            'linked_sync_title_from_source' => true,
            'linked_duration_minutes' => 45,
            'linked_slot_step_minutes' => 15,
            'linked_buffer_before_minutes' => 0,
            'linked_buffer_after_minutes' => 0,
            'linked_min_booking_notice_minutes' => 60,
            'linked_max_booking_horizon_days' => 30,
            'linked_requires_confirmation' => false,
            'linked_sort_weight' => 0,
        ]);

        $services = BookableService::query()->where('tenant_id', $tenant->id)->get();
        $this->assertCount(1, $services);
        $svc = $services->first();
        $this->assertSame($m->id, $svc->motorcycle_id);
        $this->assertNull($svc->rental_unit_id);
        $this->assertTrue($svc->is_active);
        $this->assertSame(BookableServiceLinkType::Motorcycle, $svc->linkType());
        $target = $svc->schedulingTarget;
        $this->assertNotNull($target);
        $this->assertTrue($target->scheduling_enabled);
    }

    public function test_second_enable_same_motorcycle_does_not_create_second_bookable_service(): void
    {
        $tenant = $this->createTenantWithActiveDomain('link_moto2');
        $m = $this->makeMotorcycle($tenant, 'moto-b');
        $payload = [
            'linked_booking_enabled' => true,
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
        $this->manager()->applyMotorcycleLinkedForm($m, SchedulingScope::Tenant, $payload);
        $firstId = $this->manager()->findLinkedForMotorcycle($m, SchedulingScope::Tenant)?->id;
        $this->manager()->applyMotorcycleLinkedForm($m->fresh(), SchedulingScope::Tenant, $payload);
        $secondId = $this->manager()->findLinkedForMotorcycle($m->fresh(), SchedulingScope::Tenant)?->id;
        $this->assertSame($firstId, $secondId);
        $this->assertSame(1, BookableService::query()->where('tenant_id', $tenant->id)->where('motorcycle_id', $m->id)->count());
    }

    public function test_wrong_tenant_motorcycle_rejected(): void
    {
        $tA = $this->createTenantWithActiveDomain('ta_wrong');
        $tB = $this->createTenantWithActiveDomain('tb_wrong');
        $m = $this->makeMotorcycle($tA, 'only-a');

        $this->expectException(\InvalidArgumentException::class);
        BookableService::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tB->id,
            'motorcycle_id' => $m->id,
            'rental_unit_id' => null,
            'slug' => 'bad',
            'title' => 'X',
            'duration_minutes' => 30,
            'slot_step_minutes' => 30,
            'buffer_before_minutes' => 0,
            'buffer_after_minutes' => 0,
            'min_booking_notice_minutes' => 0,
            'max_booking_horizon_days' => 30,
            'requires_confirmation' => false,
            'is_active' => true,
            'sort_weight' => 0,
            'sync_title_from_source' => true,
        ]);
    }

    public function test_disable_does_not_delete_bookable_service_row(): void
    {
        $tenant = $this->createTenantWithActiveDomain('link_dis');
        $m = $this->makeMotorcycle($tenant, 'm-dis');
        $payload = [
            'linked_booking_enabled' => true,
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
        $this->manager()->applyMotorcycleLinkedForm($m, SchedulingScope::Tenant, $payload);
        $id = $this->manager()->findLinkedForMotorcycle($m, SchedulingScope::Tenant)?->id;
        $this->assertNotNull($id);

        $this->manager()->applyMotorcycleLinkedForm($m->fresh(), SchedulingScope::Tenant, array_merge($payload, [
            'linked_booking_enabled' => false,
        ]));

        $this->assertNotNull(BookableService::query()->find($id));
        $row = BookableService::query()->find($id);
        $this->assertFalse($row->is_active);
        $this->assertFalse($row->schedulingTarget?->scheduling_enabled ?? true);
    }

    public function test_sync_title_true_updates_title_and_target_label_when_motorcycle_renamed(): void
    {
        $tenant = $this->createTenantWithActiveDomain('link_sync_t');
        $m = $this->makeMotorcycle($tenant, 'm-sync-t');
        $this->manager()->applyMotorcycleLinkedForm($m, SchedulingScope::Tenant, [
            'linked_booking_enabled' => true,
            'linked_sync_title_from_source' => true,
            'linked_duration_minutes' => 60,
            'linked_slot_step_minutes' => 15,
            'linked_buffer_before_minutes' => 0,
            'linked_buffer_after_minutes' => 0,
            'linked_min_booking_notice_minutes' => 120,
            'linked_max_booking_horizon_days' => 60,
            'linked_requires_confirmation' => true,
            'linked_sort_weight' => 0,
        ]);
        $svc = $this->manager()->findLinkedForMotorcycle($m, SchedulingScope::Tenant);
        $this->assertSame('Test Moto', $svc->title);

        $m->update(['name' => 'Renamed Moto']);
        $this->manager()->syncLinkedBookableFromMotorcycle($m->fresh());

        $svc->refresh();
        $this->assertSame('Renamed Moto', $svc->title);
        $this->assertSame('Renamed Moto', $svc->schedulingTarget->label);
    }

    public function test_sync_title_false_motorcycle_rename_does_not_change_title(): void
    {
        $tenant = $this->createTenantWithActiveDomain('link_sync_f');
        $m = $this->makeMotorcycle($tenant, 'm-sync-f');
        $this->manager()->applyMotorcycleLinkedForm($m, SchedulingScope::Tenant, [
            'linked_booking_enabled' => true,
            'linked_sync_title_from_source' => false,
            'linked_duration_minutes' => 60,
            'linked_slot_step_minutes' => 15,
            'linked_buffer_before_minutes' => 0,
            'linked_buffer_after_minutes' => 0,
            'linked_min_booking_notice_minutes' => 120,
            'linked_max_booking_horizon_days' => 60,
            'linked_requires_confirmation' => true,
            'linked_sort_weight' => 0,
        ]);
        $svc = $this->manager()->findLinkedForMotorcycle($m, SchedulingScope::Tenant);
        $custom = 'Custom title';
        $svc->update(['title' => $custom]);

        $m->update(['name' => 'Other']);
        $this->manager()->syncLinkedBookableFromMotorcycle($m->fresh());

        $this->assertSame($custom, $svc->fresh()->title);
    }

    public function test_find_linked_respects_scheduling_scope(): void
    {
        $tenant = $this->createTenantWithActiveDomain('link_scope');
        $m = $this->makeMotorcycle($tenant, 'm-scope');
        $this->manager()->applyMotorcycleLinkedForm($m, SchedulingScope::Tenant, [
            'linked_booking_enabled' => true,
            'linked_sync_title_from_source' => true,
            'linked_duration_minutes' => 60,
            'linked_slot_step_minutes' => 15,
            'linked_buffer_before_minutes' => 0,
            'linked_buffer_after_minutes' => 0,
            'linked_min_booking_notice_minutes' => 120,
            'linked_max_booking_horizon_days' => 60,
            'linked_requires_confirmation' => true,
            'linked_sort_weight' => 0,
        ]);

        $this->assertNull($this->manager()->findLinkedForMotorcycle($m, SchedulingScope::Platform));
        $this->assertNotNull($this->manager()->findLinkedForMotorcycle($m, SchedulingScope::Tenant));
    }

    public function test_repeat_operations_do_not_create_second_scheduling_target(): void
    {
        $tenant = $this->createTenantWithActiveDomain('link_tgt');
        $m = $this->makeMotorcycle($tenant, 'm-tgt');
        $payload = [
            'linked_booking_enabled' => true,
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
        $this->manager()->applyMotorcycleLinkedForm($m, SchedulingScope::Tenant, $payload);
        $svc = $this->manager()->findLinkedForMotorcycle($m, SchedulingScope::Tenant);
        $this->manager()->applyMotorcycleLinkedForm($m->fresh(), SchedulingScope::Tenant, $payload);
        $this->manager()->applyMotorcycleLinkedForm($m->fresh(), SchedulingScope::Tenant, $payload);

        $count = SchedulingTarget::query()
            ->where('tenant_id', $tenant->id)
            ->where('target_type', SchedulingTargetType::BookableService)
            ->where('target_id', $svc->id)
            ->count();
        $this->assertSame(1, $count);
    }

    public function test_both_foreign_keys_rejected_by_assert_integrity(): void
    {
        $tenant = $this->createTenantWithActiveDomain('link_xor');
        $m = $this->makeMotorcycle($tenant, 'm-xor');
        $unit = RentalUnit::query()->create([
            'tenant_id' => $tenant->id,
            'motorcycle_id' => $m->id,
            'status' => 'active',
        ]);
        $svc = BookableService::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'motorcycle_id' => $m->id,
            'rental_unit_id' => null,
            'slug' => 'xor-svc',
            'title' => 'T',
            'duration_minutes' => 30,
            'slot_step_minutes' => 30,
            'buffer_before_minutes' => 0,
            'buffer_after_minutes' => 0,
            'min_booking_notice_minutes' => 0,
            'max_booking_horizon_days' => 30,
            'requires_confirmation' => false,
            'is_active' => false,
            'sort_weight' => 0,
            'sync_title_from_source' => true,
        ]);
        $svc->rental_unit_id = $unit->id;

        $this->expectException(BookableServiceIntegrityException::class);
        $this->manager()->assertIntegrity($svc);
    }

    public function test_rental_unit_linked_resolved_motorcycle_and_label(): void
    {
        $tenant = $this->createTenantWithActiveDomain('link_ru');
        $m = $this->makeMotorcycle($tenant, 'm-ru');
        $unit = RentalUnit::query()->create([
            'tenant_id' => $tenant->id,
            'motorcycle_id' => $m->id,
            'external_id' => 'EXT-1',
            'status' => 'active',
        ]);

        $this->manager()->applyRentalUnitLinkedForm($unit, SchedulingScope::Tenant, [
            'linked_booking_enabled' => true,
            'linked_sync_title_from_source' => true,
            'linked_duration_minutes' => 30,
            'linked_slot_step_minutes' => 15,
            'linked_buffer_before_minutes' => 0,
            'linked_buffer_after_minutes' => 0,
            'linked_min_booking_notice_minutes' => 0,
            'linked_max_booking_horizon_days' => 30,
            'linked_requires_confirmation' => false,
            'linked_sort_weight' => 0,
        ]);

        $svc = $this->manager()->findLinkedForRentalUnit($unit, SchedulingScope::Tenant);
        $this->assertNotNull($svc);
        $this->assertSame($m->id, $svc->resolvedMotorcycle()?->id);
        $this->assertStringContainsString('Test Moto', RentalUnitSchedulingLabel::label($unit));
    }

    public function test_enable_online_booking_for_service_activates_target(): void
    {
        $tenant = $this->createTenantWithActiveDomain('mgr_en_svc');
        $svc = BookableService::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'motorcycle_id' => null,
            'rental_unit_id' => null,
            'slug' => 'standalone-en',
            'title' => 'Standalone',
            'duration_minutes' => 60,
            'slot_step_minutes' => 15,
            'buffer_before_minutes' => 0,
            'buffer_after_minutes' => 0,
            'min_booking_notice_minutes' => 120,
            'max_booking_horizon_days' => 60,
            'requires_confirmation' => true,
            'is_active' => false,
            'sort_weight' => 0,
            'sync_title_from_source' => true,
        ]);
        $svc->schedulingTarget?->update(['scheduling_enabled' => false]);

        $this->manager()->enableOnlineBookingForService($svc->fresh());

        $svc->refresh();
        $svc->unsetRelation('schedulingTarget');
        $this->assertTrue($svc->is_active);
        $this->assertTrue($svc->schedulingTarget?->scheduling_enabled ?? false);
    }

    public function test_disable_online_booking_for_service_alias_matches_disable_linked(): void
    {
        $tenant = $this->createTenantWithActiveDomain('mgr_dis_alias');
        $m = $this->makeMotorcycle($tenant, 'm-dis-alias');
        $this->manager()->applyMotorcycleLinkedForm($m, SchedulingScope::Tenant, [
            'linked_booking_enabled' => true,
            'linked_sync_title_from_source' => true,
            'linked_duration_minutes' => 60,
            'linked_slot_step_minutes' => 15,
            'linked_buffer_before_minutes' => 0,
            'linked_buffer_after_minutes' => 0,
            'linked_min_booking_notice_minutes' => 120,
            'linked_max_booking_horizon_days' => 60,
            'linked_requires_confirmation' => true,
            'linked_sort_weight' => 0,
        ]);
        $svc = $this->manager()->findLinkedForMotorcycle($m, SchedulingScope::Tenant);
        $this->manager()->disableOnlineBookingForService($svc);
        $svc->refresh();
        $svc->unsetRelation('schedulingTarget');
        $this->assertFalse($svc->is_active);
        $this->assertFalse($svc->schedulingTarget?->scheduling_enabled ?? true);
    }

    public function test_ensure_linked_service_for_motorcycle_creates_inactive_row(): void
    {
        $tenant = $this->createTenantWithActiveDomain('mgr_ensure');
        $m = $this->makeMotorcycle($tenant, 'm-ensure');
        $svc = $this->manager()->ensureLinkedServiceForMotorcycle($m, SchedulingScope::Tenant);
        $this->assertFalse($svc->is_active);
        $this->assertSame($m->id, $svc->motorcycle_id);
        $this->assertSame(1, BookableService::query()->where('tenant_id', $tenant->id)->where('motorcycle_id', $m->id)->count());
    }

    public function test_apply_scheduling_settings_to_service_updates_duration(): void
    {
        $tenant = $this->createTenantWithActiveDomain('mgr_apply_set');
        $m = $this->makeMotorcycle($tenant, 'm-apply-set');
        $svc = $this->manager()->ensureLinkedServiceForMotorcycle($m, SchedulingScope::Tenant);
        $this->manager()->applySchedulingSettingsToService($svc, [
            'duration_minutes' => 40,
            'slot_step_minutes' => 10,
        ]);
        $svc->refresh();
        $this->assertSame(40, $svc->duration_minutes);
        $this->assertSame(10, $svc->slot_step_minutes);
    }

    public function test_public_bookable_services_json_contains_link_fields(): void
    {
        $tenant = $this->createTenantWithActiveDomain('link_api');
        $host = $this->tenancyHostForSlug('link_api');
        $m = $this->makeMotorcycle($tenant, 'm-api');
        $this->manager()->applyMotorcycleLinkedForm($m, SchedulingScope::Tenant, [
            'linked_booking_enabled' => true,
            'linked_sync_title_from_source' => true,
            'linked_duration_minutes' => 60,
            'linked_slot_step_minutes' => 15,
            'linked_buffer_before_minutes' => 0,
            'linked_buffer_after_minutes' => 0,
            'linked_min_booking_notice_minutes' => 120,
            'linked_max_booking_horizon_days' => 60,
            'linked_requires_confirmation' => true,
            'linked_sort_weight' => 0,
        ]);

        $response = $this->getJson('http://'.$host.'/api/tenant/scheduling/bookable-services');
        $response->assertOk();
        $row = $response->json('services.0');
        $this->assertSame('motorcycle', $row['link_type']);
        $this->assertSame($m->id, $row['motorcycle_id']);
        $this->assertSame('m-api', $row['motorcycle_slug']);
        $this->assertSame('Test Moto', $row['motorcycle_name']);
        $this->assertNull($row['rental_unit_id']);
    }
}
