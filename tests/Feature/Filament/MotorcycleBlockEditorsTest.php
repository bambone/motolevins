<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Tenant\Resources\MotorcycleResource\Pages\EditMotorcycle;
use App\Livewire\Tenant\Motorcycles\MotorcycleMainInfoEditor;
use App\Models\Motorcycle;
use App\Models\User;
use App\Services\CurrentTenantManager;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

final class MotorcycleBlockEditorsTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_edit_motorcycle_shell_has_no_global_save_changes_action(): void
    {
        $tenant = $this->createTenantWithActiveDomain('fil_moto_block_shell');
        $m = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Shell Save Test',
            'slug' => 'shell-save-test',
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
            ->assertDontSee('Сохранить изменения', false);
    }

    public function test_edit_motorcycle_shell_save_does_not_update_record(): void
    {
        $tenant = $this->createTenantWithActiveDomain('fil_moto_block_noop');
        $m = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Noop Save Test',
            'slug' => 'noop-save-test',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 1000,
        ]);

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'tenant_owner', 'status' => 'active']);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($user);
        app(CurrentTenantManager::class)->setTenant($tenant);

        $before = $m->fresh()->updated_at;

        Livewire::test(EditMotorcycle::class, ['record' => $m->getKey()])
            ->call('save')
            ->assertSuccessful();

        $this->assertTrue($m->fresh()->updated_at->equalTo($before));
        $this->assertSame('Noop Save Test', $m->fresh()->name);
    }

    public function test_motorcycle_main_info_editor_saves_whitelisted_fields(): void
    {
        $tenant = $this->createTenantWithActiveDomain('fil_moto_main_block');
        $m = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Main Block',
            'slug' => 'main-block',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 1000,
            'brand' => 'OldBrand',
        ]);

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'tenant_owner', 'status' => 'active']);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($user);
        app(CurrentTenantManager::class)->setTenant($tenant);

        Livewire::test(MotorcycleMainInfoEditor::class, ['recordId' => (int) $m->getKey()])
            ->set('data.brand', 'NewBrand')
            ->call('save')
            ->assertSuccessful();

        $this->assertSame('NewBrand', $m->fresh()->brand);
        $this->assertSame('Main Block', $m->fresh()->name);
    }

    public function test_motorcycle_main_info_editor_validation_failure_preserves_local_state(): void
    {
        $tenant = $this->createTenantWithActiveDomain('fil_moto_main_val');
        $m = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Valid Name',
            'slug' => 'valid-name',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 1000,
        ]);

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'tenant_owner', 'status' => 'active']);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($user);
        app(CurrentTenantManager::class)->setTenant($tenant);

        Livewire::test(MotorcycleMainInfoEditor::class, ['recordId' => (int) $m->getKey()])
            ->set('data.name', '')
            ->call('save')
            ->assertHasErrors(['data.name']);

        $this->assertSame('Valid Name', $m->fresh()->name);
    }
}
