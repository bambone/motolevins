<?php

declare(strict_types=1);

namespace Tests\Unit\Tenant\Filament;

use App\Models\Tenant;
use App\Models\User;
use App\Tenant\Filament\TenantCabinetUserPicker;
use App\Tenant\Filament\TenantPanelSelectScope;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class TenantCabinetUserPickerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_belongs_to_cabinet_team_when_active_owner(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'T',
            'slug' => 't-'.uniqid(),
            'status' => 'active',
        ]);
        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'tenant_owner', 'status' => 'active']);

        $this->assertTrue(TenantCabinetUserPicker::userBelongsToCabinetTeam($tenant->id, $user->id));
    }

    public function test_user_not_in_team_for_other_tenant(): void
    {
        $a = Tenant::query()->create(['name' => 'A', 'slug' => 'a-'.uniqid(), 'status' => 'active']);
        $b = Tenant::query()->create(['name' => 'B', 'slug' => 'b-'.uniqid(), 'status' => 'active']);
        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($b->id, ['role' => 'tenant_owner', 'status' => 'active']);

        $this->assertFalse(TenantCabinetUserPicker::userBelongsToCabinetTeam($a->id, $user->id));
    }

    public function test_user_not_in_team_when_pivot_not_active(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'T',
            'slug' => 't-'.uniqid(),
            'status' => 'active',
        ]);
        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'tenant_owner', 'status' => 'suspended']);

        $this->assertFalse(TenantCabinetUserPicker::userBelongsToCabinetTeam($tenant->id, $user->id));
    }

    public function test_assert_throws_for_foreign_user(): void
    {
        $a = Tenant::query()->create(['name' => 'A', 'slug' => 'a-'.uniqid(), 'status' => 'active']);
        $b = Tenant::query()->create(['name' => 'B', 'slug' => 'b-'.uniqid(), 'status' => 'active']);
        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($b->id, ['role' => 'tenant_owner', 'status' => 'active']);

        $this->expectException(ValidationException::class);
        TenantCabinetUserPicker::assertUserBelongsToCabinetTeam($a->id, $user->id);
    }

    public function test_apply_cabinet_team_scope_excludes_other_tenant_users(): void
    {
        $a = Tenant::query()->create(['name' => 'A', 'slug' => 'a-'.uniqid(), 'status' => 'active']);
        $b = Tenant::query()->create(['name' => 'B', 'slug' => 'b-'.uniqid(), 'status' => 'active']);
        $userA = User::factory()->create(['status' => 'active', 'name' => 'UserA']);
        $userB = User::factory()->create(['status' => 'active', 'name' => 'UserB']);
        $userA->tenants()->attach($a->id, ['role' => 'tenant_owner', 'status' => 'active']);
        $userB->tenants()->attach($b->id, ['role' => 'tenant_owner', 'status' => 'active']);

        $ids = User::query()
            ->tap(fn ($q) => TenantCabinetUserPicker::applyCabinetTeamScope($q, $a->id))
            ->pluck('id')
            ->all();

        $this->assertContains($userA->id, $ids);
        $this->assertNotContains($userB->id, $ids);
    }

    public function test_name_options_for_cabinet_excludes_other_tenant(): void
    {
        $a = Tenant::query()->create(['name' => 'A', 'slug' => 'a-'.uniqid(), 'status' => 'active']);
        $b = Tenant::query()->create(['name' => 'B', 'slug' => 'b-'.uniqid(), 'status' => 'active']);
        $userA = User::factory()->create(['status' => 'active', 'name' => 'UserA']);
        $userB = User::factory()->create(['status' => 'active', 'name' => 'UserB']);
        $userA->tenants()->attach($a->id, ['role' => 'tenant_owner', 'status' => 'active']);
        $userB->tenants()->attach($b->id, ['role' => 'tenant_owner', 'status' => 'active']);

        $opts = TenantCabinetUserPicker::nameOptionsForCabinet($a->id, null);

        $this->assertArrayHasKey($userA->id, $opts);
        $this->assertArrayNotHasKey($userB->id, $opts);
    }

    public function test_apply_tenant_owned_scope_null_tenant_yields_empty(): void
    {
        Tenant::query()->create(['name' => 'T', 'slug' => 't-'.uniqid(), 'status' => 'active']);

        $count = Tenant::query()
            ->tap(fn ($q) => TenantPanelSelectScope::applyTenantOwnedScope($q, null))
            ->count();

        $this->assertSame(0, $count);
    }
}
