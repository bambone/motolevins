<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\Motorcycle;
use App\Models\RentalUnit;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CurrentTenantManager;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

final class RentalUnitPolicyGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_fleet_manager_can_update_rental_unit_without_manage_integrations(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Fleet tenant',
            'slug' => 'fleet-tenant',
            'status' => 'active',
        ]);

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, [
            'role' => 'fleet_manager',
            'status' => 'active',
        ]);

        $bike = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Policy bike',
            'slug' => 'policy-bike',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 1000,
        ]);

        $unit = RentalUnit::query()->create([
            'tenant_id' => $tenant->id,
            'motorcycle_id' => $bike->id,
            'status' => 'active',
        ]);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        app(CurrentTenantManager::class)->setTenant($tenant);

        $this->assertTrue(Gate::forUser($user)->allows('manage_motorcycles'));
        $this->assertFalse(Gate::forUser($user)->allows('manage_integrations'));
        $this->assertTrue(Gate::forUser($user)->allows('update', $unit));

        Filament::setCurrentPanel(null);
        app(CurrentTenantManager::class)->setTenant(null);
    }
}
