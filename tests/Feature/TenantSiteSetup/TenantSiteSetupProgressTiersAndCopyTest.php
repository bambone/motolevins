<?php

namespace Tests\Feature\TenantSiteSetup;

use App\Models\TenantSetting;
use App\Models\User;
use App\Tenant\CurrentTenant;
use App\TenantSiteSetup\SetupItemRegistry;
use App\TenantSiteSetup\SetupItemUrlResolver;
use App\TenantSiteSetup\SetupProgressCache;
use App\TenantSiteSetup\SetupProgressService;
use App\TenantSiteSetup\TenantSiteSetupFeature;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

class TenantSiteSetupProgressTiersAndCopyTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_summary_includes_quick_and_extended_tier_metrics_for_expert_auto(): void
    {
        config(['features.tenant_site_setup_framework' => true]);
        $this->assertTrue(TenantSiteSetupFeature::enabled());

        $tenant = $this->createTenantWithActiveDomain('ts_tiers', ['theme_key' => 'expert_auto']);
        $this->actingAsTenant($tenant);

        $summary = app(SetupProgressService::class)->computeSummary($tenant);

        $this->assertSame(10, $summary['applicable_count']);
        $this->assertSame(8, $summary['quick_launch_applicable']);
        $this->assertSame(2, $summary['extended_applicable']);
        $this->assertSame(0, $summary['quick_launch_completed']);
        $this->assertSame(0, $summary['extended_completed']);
        $this->assertSame(0, $summary['quick_launch_percent']);
        $this->assertSame(0, $summary['extended_percent']);
    }

    public function test_summary_tier_counts_after_one_quick_item_completed(): void
    {
        config(['features.tenant_site_setup_framework' => true]);
        $tenant = $this->createTenantWithActiveDomain('ts_tiers_done', ['theme_key' => 'expert_auto']);
        $this->actingAsTenant($tenant);

        TenantSetting::setForTenant($tenant->id, 'general.site_name', 'Название для теста');
        SetupProgressCache::forget((int) $tenant->id);

        $summary = app(SetupProgressService::class)->computeSummary($tenant);

        $this->assertSame(1, $summary['quick_launch_completed']);
        $this->assertSame(0, $summary['extended_completed']);
        $this->assertGreaterThan(0, $summary['quick_launch_percent']);
        $this->assertSame(0, $summary['extended_percent']);
    }

    public function test_settings_logo_url_includes_settings_tab_appearance(): void
    {
        $tenant = $this->createTenantWithActiveDomain('ts_url', ['theme_key' => 'expert_auto']);
        $defs = SetupItemRegistry::definitions();
        $def = $defs['settings.logo'];
        $url = app(SetupItemUrlResolver::class)->urlFor($tenant, $def);

        $this->assertIsString($url);
        $parts = parse_url((string) $url);
        $this->assertIsArray($parts);
        $this->assertArrayHasKey('query', $parts);
        parse_str((string) $parts['query'], $q);
        $this->assertArrayHasKey('settings_tab', $q);
        $this->assertSame('appearance', $q['settings_tab']);
    }

    public function test_site_setup_overview_shows_honest_progress_copy(): void
    {
        config(['features.tenant_site_setup_framework' => true]);
        $this->assertTrue(TenantSiteSetupFeature::enabled());

        $tenant = $this->createTenantWithActiveDomain('ts_copy', ['theme_key' => 'expert_auto']);
        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'tenant_owner', 'status' => 'active']);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $domain = $tenant->domains()->where('is_primary', true)->first();
        $this->app->instance(
            CurrentTenant::class,
            new CurrentTenant($tenant, $domain, false, $this->tenancyHostForSlug((string) $tenant->slug))
        );

        $this->actingAs($user);
        $this->withoutVite();

        $host = $this->tenancyHostForSlug('ts_copy');
        $response = $this->get('http://'.$host.'/admin/site-setup');
        $response->assertOk();
        $response->assertSee('Считается по чеклисту запуска', false);
        $response->assertSee('сводка по всем пунктам текущего чеклиста', false);
    }

    public function test_dashboard_widget_includes_honest_checklist_copy(): void
    {
        config(['features.tenant_site_setup_framework' => true]);
        $this->assertTrue(TenantSiteSetupFeature::enabled());

        $tenant = $this->createTenantWithActiveDomain('ts_wcopy', ['theme_key' => 'expert_auto']);
        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'tenant_owner', 'status' => 'active']);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $domain = $tenant->domains()->where('is_primary', true)->first();
        $this->app->instance(
            CurrentTenant::class,
            new CurrentTenant($tenant, $domain, false, $this->tenancyHostForSlug((string) $tenant->slug))
        );

        $this->actingAs($user);
        $this->withoutVite();

        $host = $this->tenancyHostForSlug('ts_wcopy');
        $response = $this->get('http://'.$host.'/admin');
        $response->assertOk();
        $response->assertSee('Сводка по чеклисту', false);
        $response->assertSee('Чеклист мастера', false);
    }

    protected function tearDown(): void
    {
        Filament::setCurrentPanel(null);
        parent::tearDown();
    }

    private function actingAsTenant(\App\Models\Tenant $tenant): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'tenant_owner', 'status' => 'active']);
        $this->actingAs($user);
    }
}
