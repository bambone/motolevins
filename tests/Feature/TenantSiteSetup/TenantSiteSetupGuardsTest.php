<?php

namespace Tests\Feature\TenantSiteSetup;

use App\Models\User;
use App\Tenant\CurrentTenant;
use App\TenantSiteSetup\SetupItemStateService;
use App\TenantSiteSetup\SetupSessionService;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

class TenantSiteSetupGuardsTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_cannot_post_session_action(): void
    {
        config(['features.tenant_site_setup_framework' => true]);
        $this->assertTrue(Route::has('filament.admin.tenant-site-setup.session'));

        $response = $this->post(route('filament.admin.tenant-site-setup.session'), [
            'action' => 'pause',
            '_token' => csrf_token(),
        ]);

        $this->assertNotEquals(200, $response->getStatusCode());
    }

    public function test_resume_reactivates_paused_session_same_row(): void
    {
        config(['features.tenant_site_setup_framework' => true]);
        $tenant = $this->createTenantWithActiveDomain('ts_resume', ['theme_key' => 'expert_auto']);
        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'tenant_owner', 'status' => 'active']);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $domain = $tenant->domains()->where('is_primary', true)->first();
        $this->app->instance(
            CurrentTenant::class,
            new CurrentTenant($tenant, $domain, false, $this->tenancyHostForSlug((string) $tenant->slug))
        );
        $this->actingAs($user);

        $sessions = app(SetupSessionService::class);
        $first = $sessions->startOrResume($tenant, $user);
        $firstId = $first->id;
        $sessions->pause($tenant, $user);

        $this->assertDatabaseHas('tenant_setup_sessions', [
            'id' => $firstId,
            'session_status' => 'paused',
        ]);

        $second = $sessions->startOrResume($tenant, $user);
        $this->assertSame($firstId, $second->id);
        $this->assertSame('active', $second->session_status);
    }

    public function test_start_fresh_creates_new_session_when_paused(): void
    {
        config(['features.tenant_site_setup_framework' => true]);
        $tenant = $this->createTenantWithActiveDomain('ts_fresh', ['theme_key' => 'expert_auto']);
        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'tenant_owner', 'status' => 'active']);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $domain = $tenant->domains()->where('is_primary', true)->first();
        $this->app->instance(
            CurrentTenant::class,
            new CurrentTenant($tenant, $domain, false, $this->tenancyHostForSlug((string) $tenant->slug))
        );
        $this->actingAs($user);

        $sessions = app(SetupSessionService::class);
        $paused = $sessions->startOrResume($tenant, $user);
        $pausedId = $paused->id;
        $sessions->pause($tenant, $user);

        $fresh = $sessions->startFreshGuidedSession($tenant, $user);
        $this->assertNotSame($pausedId, $fresh->id);
        $this->assertSame('active', $fresh->session_status);

        $this->assertDatabaseHas('tenant_setup_sessions', [
            'id' => $pausedId,
            'session_status' => 'abandoned',
        ]);
    }

    public function test_restore_item_rejects_unknown_key(): void
    {
        config(['features.tenant_site_setup_framework' => true]);
        $tenant = $this->createTenantWithActiveDomain('ts_rst', ['theme_key' => 'expert_auto']);
        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'tenant_owner', 'status' => 'active']);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $domain = $tenant->domains()->where('is_primary', true)->first();
        $this->app->instance(
            CurrentTenant::class,
            new CurrentTenant($tenant, $domain, false, $this->tenancyHostForSlug((string) $tenant->slug))
        );
        $this->actingAs($user);

        $host = $this->tenancyHostForSlug((string) $tenant->slug);
        $response = $this->postWithHost($host, '/admin/tenant-site-setup/items/restore', [
            'item_key' => 'nonexistent.key',
            '_token' => csrf_token(),
        ]);

        $response->assertInvalid(['item_key']);
    }

    private function postWithHost(string $host, string $path, array $data): TestResponse
    {
        $path = str_starts_with($path, '/') ? $path : '/'.$path;

        return $this->call('POST', 'http://'.$host.$path, $data);
    }

    public function test_not_needed_on_launch_critical_blocked_when_not_allowed(): void
    {
        config(['features.tenant_site_setup_framework' => true]);
        $tenant = $this->createTenantWithActiveDomain('ts_nn2', ['theme_key' => 'expert_auto']);
        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'tenant_owner', 'status' => 'active']);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $domain = $tenant->domains()->where('is_primary', true)->first();
        $this->app->instance(
            CurrentTenant::class,
            new CurrentTenant($tenant, $domain, false, $this->tenancyHostForSlug((string) $tenant->slug))
        );
        $this->actingAs($user);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(SetupItemStateService::class)->markNotNeeded($tenant, $user, 'settings.site_name', 'test', null);
    }

    protected function tearDown(): void
    {
        Filament::setCurrentPanel(null);
        parent::tearDown();
    }
}
