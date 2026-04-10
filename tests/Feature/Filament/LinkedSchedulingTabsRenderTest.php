<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Tenant\Forms\LinkedBookableSchedulingForm;
use App\Filament\Tenant\Resources\MotorcycleResource\Pages\EditMotorcycle;
use App\Filament\Tenant\Resources\RentalUnitResource\Pages\EditRentalUnit;
use App\Livewire\Tenant\Motorcycles\MotorcycleSchedulingEditor;
use App\Models\Motorcycle;
use App\Models\RentalUnit;
use App\Models\User;
use App\Services\CurrentTenantManager;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

final class LinkedSchedulingTabsRenderTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_edit_motorcycle_page_renders_with_scheduling_ui(): void
    {
        $tenant = $this->createTenantWithActiveDomain('fil_link_tab_moto');
        $m = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Tab Test Moto',
            'slug' => 'tab-test-moto',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 1000,
        ]);

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'tenant_owner', 'status' => 'active']);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($user);
        app(CurrentTenantManager::class)->setTenant($tenant);

        Livewire::test(EditMotorcycle::class, ['record' => $m->getKey()])
            ->assertSuccessful()
            ->assertDontSee('Сначала сохраните карточку', false)
            ->assertSee('Онлайн-запись', false)
            ->assertSee('ВЫКЛЮЧЕНА', false)
            ->assertSee('сохраните блок «Онлайн-запись»', false);
    }

    public function test_edit_rental_unit_page_renders_with_scheduling_ui(): void
    {
        $tenant = $this->createTenantWithActiveDomain('fil_link_tab_ru');
        $m = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Tab Test Moto RU',
            'slug' => 'tab-test-moto-ru',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 1000,
        ]);
        $ru = RentalUnit::query()->create([
            'tenant_id' => $tenant->id,
            'motorcycle_id' => $m->id,
            'status' => 'active',
        ]);

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'tenant_owner', 'status' => 'active']);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($user);
        app(CurrentTenantManager::class)->setTenant($tenant);

        Livewire::test(EditRentalUnit::class, ['record' => $ru->getKey()])
            ->assertSuccessful()
            ->assertSee('Онлайн-запись', false)
            ->assertSee('ВЫКЛЮЧЕНА', false);
    }

    public function test_locked_tenant_linked_form_data_for_motorcycle_is_empty_and_scheduling_save_noops(): void
    {
        $tenant = $this->createTenantWithActiveDomain('fil_link_locked', ['scheduling_module_enabled' => false]);
        $m = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Locked Moto',
            'slug' => 'locked-moto',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 500,
        ]);

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'tenant_owner', 'status' => 'active']);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($user);
        app(CurrentTenantManager::class)->setTenant($tenant);

        $this->assertTrue(LinkedBookableSchedulingForm::schedulingLocked());
        $this->assertFalse(LinkedBookableSchedulingForm::schedulingSectionVisible());
        $this->assertSame([], LinkedBookableSchedulingForm::linkedFormDataForMotorcycle($m));

        Livewire::test(MotorcycleSchedulingEditor::class, ['recordId' => (int) $m->getKey()])
            ->call('save')
            ->assertSuccessful();
    }
}
