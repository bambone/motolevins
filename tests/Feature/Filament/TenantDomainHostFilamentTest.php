<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Platform\Resources\TenantDomainResource\Pages\CreateTenantDomain;
use App\Filament\Platform\Resources\TenantDomainResource\Pages\ListTenantDomains;
use App\Filament\Tenant\Resources\CustomDomainResource\Pages\CreateCustomDomain;
use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Models\User;
use App\Services\CurrentTenantManager;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class TenantDomainHostFilamentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_platform_create_rejects_invalid_host(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('platform_owner');

        $tenant = Tenant::query()->create([
            'name' => 'ACME',
            'slug' => 'acme',
            'status' => 'active',
        ]);

        Filament::setCurrentPanel(Filament::getPanel('platform'));
        $this->actingAs($user);

        Livewire::test(CreateTenantDomain::class)
            ->fillForm([
                'tenant_id' => $tenant->id,
                'host' => 'invalid domain',
                'type' => TenantDomain::TYPE_CUSTOM,
                'is_primary' => false,
                'status' => TenantDomain::STATUS_PENDING,
                'ssl_status' => TenantDomain::SSL_PENDING,
            ])
            ->call('create')
            ->assertHasErrors(['data.host']);
    }

    public function test_platform_create_accepts_valid_custom_host(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('platform_owner');

        $tenant = Tenant::query()->create([
            'name' => 'ACME2',
            'slug' => 'acme2',
            'status' => 'active',
        ]);

        Filament::setCurrentPanel(Filament::getPanel('platform'));
        $this->actingAs($user);

        Livewire::test(CreateTenantDomain::class)
            ->fillForm([
                'tenant_id' => $tenant->id,
                'host' => 'Example.COM',
                'type' => TenantDomain::TYPE_CUSTOM,
                'is_primary' => false,
                'status' => TenantDomain::STATUS_PENDING,
                'ssl_status' => TenantDomain::SSL_PENDING,
            ])
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tenant_domains', [
            'tenant_id' => $tenant->id,
            'host' => 'example.com',
            'type' => TenantDomain::TYPE_CUSTOM,
        ]);
    }

    public function test_tenant_panel_create_rejects_invalid_host(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $tenant = Tenant::query()->create([
            'name' => 'Shop',
            'slug' => 'shopfil',
            'status' => 'active',
        ]);
        $user->tenants()->attach($tenant->id, ['role' => 'tenant_owner', 'status' => 'active']);

        TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'host' => 'shopfil.apex.test',
            'type' => TenantDomain::TYPE_SUBDOMAIN,
            'is_primary' => true,
            'status' => TenantDomain::STATUS_ACTIVE,
            'ssl_status' => TenantDomain::SSL_NOT_REQUIRED,
            'verified_at' => now(),
            'activated_at' => now(),
        ]);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($user);
        app(CurrentTenantManager::class)->setTenant($tenant);

        Livewire::test(CreateCustomDomain::class)
            ->fillForm([
                'host' => 'http://bad.example',
            ])
            ->call('create')
            ->assertHasErrors(['data.host']);
    }

    public function test_platform_table_slide_over_edit_saves_without_false_client_error(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('platform_owner');

        $tenant = Tenant::query()->create([
            'name' => 'Slide Tenant',
            'slug' => 'slide-tenant',
            'status' => 'active',
        ]);

        $domain = TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'host' => 'slide-tenant.apex.test',
            'type' => TenantDomain::TYPE_SUBDOMAIN,
            'is_primary' => true,
            'status' => TenantDomain::STATUS_ACTIVE,
            'ssl_status' => TenantDomain::SSL_NOT_REQUIRED,
        ]);

        Filament::setCurrentPanel(Filament::getPanel('platform'));
        $this->actingAs($user);

        // Omit `tenant_id` in the submit payload: slide-over uses `ListRecords`, not `EditRecord`;
        // the rule must resolve the client from the mounted table action record.
        Livewire::test(ListTenantDomains::class)
            ->callTableAction('edit', $domain, data: [
                'host' => 'slide-renamed.apex.test',
                'type' => TenantDomain::TYPE_SUBDOMAIN,
                'is_primary' => true,
                'status' => TenantDomain::STATUS_ACTIVE,
                'ssl_status' => TenantDomain::SSL_NOT_REQUIRED,
            ])
            ->assertHasNoTableActionErrors();

        $domain->refresh();
        $this->assertSame('slide-renamed.apex.test', $domain->host);
    }
}
