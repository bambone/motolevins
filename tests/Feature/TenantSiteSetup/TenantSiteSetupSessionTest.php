<?php

namespace Tests\Feature\TenantSiteSetup;

use App\Models\User;
use App\Tenant\CurrentTenant;
use App\TenantSiteSetup\SetupJourneyBuilder;
use App\TenantSiteSetup\SetupItemStateService;
use App\TenantSiteSetup\SetupSessionService;
use App\TenantSiteSetup\TenantSiteSetupFeature;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

class TenantSiteSetupSessionTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_advance_to_next_moves_current_item(): void
    {
        config(['features.tenant_site_setup_framework' => true]);
        $this->assertTrue(TenantSiteSetupFeature::enabled());

        $tenant = $this->createTenantWithActiveDomain('ts_sess', ['theme_key' => 'expert_auto']);
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
        $session = $sessions->startOrResume($tenant, $user);
        $first = $session->current_item_key;
        $this->assertNotNull($first);

        $sessions->advanceToNext($tenant, $user);
        $session->refresh();
        $this->assertNotSame($first, $session->current_item_key);
    }

    public function test_snoozed_item_excluded_from_journey(): void
    {
        config(['features.tenant_site_setup_framework' => true]);
        $tenant = $this->createTenantWithActiveDomain('ts_snoo', ['theme_key' => 'expert_auto']);
        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'tenant_owner', 'status' => 'active']);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $domain = $tenant->domains()->where('is_primary', true)->first();
        $this->app->instance(
            CurrentTenant::class,
            new CurrentTenant($tenant, $domain, false, $this->tenancyHostForSlug((string) $tenant->slug))
        );
        $this->actingAs($user);

        $keysBefore = app(SetupJourneyBuilder::class)->visibleStepKeys($tenant);
        $this->assertNotEmpty($keysBefore);

        app(SetupItemStateService::class)->markSnoozed($tenant, $user, $keysBefore[0], 'test', null);

        $keysAfter = app(SetupJourneyBuilder::class)->visibleStepKeys($tenant);
        $this->assertNotContains($keysBefore[0], $keysAfter);
    }

    public function test_overlay_payload_includes_session_action_url(): void
    {
        config(['features.tenant_site_setup_framework' => true]);
        $tenant = $this->createTenantWithActiveDomain('ts_pay', ['theme_key' => 'expert_auto']);
        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'tenant_owner', 'status' => 'active']);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $domain = $tenant->domains()->where('is_primary', true)->first();
        $this->app->instance(
            CurrentTenant::class,
            new CurrentTenant($tenant, $domain, false, $this->tenancyHostForSlug((string) $tenant->slug))
        );
        $this->actingAs($user);

        app(SetupSessionService::class)->startOrResume($tenant, $user);
        $payload = app(SetupSessionService::class)->overlayPayload($tenant, $user);
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('session_action_url', $payload);
        $this->assertStringContainsString('tenant-site-setup/session', (string) $payload['session_action_url']);
        $this->assertArrayHasKey('can_snooze', $payload);
    }

    public function test_profile_repository_merged_includes_defaults(): void
    {
        $tenant = $this->createTenantWithActiveDomain('ts_prof', ['theme_key' => 'expert_auto']);
        $merged = app(\App\TenantSiteSetup\SetupProfileRepository::class)->getMerged($tenant->id);
        $this->assertArrayHasKey('business_focus', $merged);
        $this->assertArrayHasKey('primary_goal', $merged);
        $this->assertSame(1, $merged['schema_version']);
    }

    protected function tearDown(): void
    {
        Filament::setCurrentPanel(null);
        parent::tearDown();
    }
}
