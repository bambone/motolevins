<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Filament\Tenant\Resources\SchedulingResourceResource\Pages\CreateSchedulingResource;
use App\Models\User;
use App\Scheduling\Enums\SchedulingScope;
use App\Services\CurrentTenantManager;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

/**
 * Серверная валидация user_id в create-странице: нельзя подставить пользователя чужого тенанта.
 * (Через UI селект чужой user_id обычно не попадает в state — проверяем именно mutateFormDataBeforeCreate.)
 */
final class TenantCabinetUserPickerFilamentTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_create_scheduling_resource_mutate_rejects_user_from_other_tenant(): void
    {
        $tenantA = $this->createTenantWithActiveDomain('picker_fil_a');
        $tenantB = $this->createTenantWithActiveDomain('picker_fil_b');

        $ownerA = User::factory()->create(['status' => 'active']);
        $ownerA->tenants()->attach($tenantA->id, ['role' => 'tenant_owner', 'status' => 'active']);

        $userB = User::factory()->create(['status' => 'active']);
        $userB->tenants()->attach($tenantB->id, ['role' => 'tenant_owner', 'status' => 'active']);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($ownerA);
        app(CurrentTenantManager::class)->setTenant($tenantA);

        $component = Livewire::test(CreateSchedulingResource::class)->instance();
        $mutate = new ReflectionMethod(CreateSchedulingResource::class, 'mutateFormDataBeforeCreate');
        $mutate->setAccessible(true);

        $this->expectException(ValidationException::class);
        $mutate->invoke($component, [
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenantA->id,
            'label' => 'Resource',
            'resource_type' => 'person',
            'user_id' => $userB->id,
        ]);
    }
}
