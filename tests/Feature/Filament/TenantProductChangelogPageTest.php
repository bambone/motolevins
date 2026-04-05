<?php

namespace Tests\Feature\Filament;

use App\Filament\Tenant\Pages\TenantProductChangelogPage;
use App\Models\PlatformProductChangelogEntry;
use App\Models\Tenant;
use App\Models\User;
use App\Tenant\CurrentTenant;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

class TenantProductChangelogPageTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    protected function tearDown(): void
    {
        Filament::setCurrentPanel(null);
        parent::tearDown();
    }

    private function bindTenantFilamentContext(Tenant $tenant): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $domain = $tenant->domains()->where('is_primary', true)->first();
        $this->app->instance(
            CurrentTenant::class,
            new CurrentTenant($tenant, $domain, false, $this->tenancyHostForSlug((string) $tenant->slug))
        );
    }

    private function tenantOwner(Tenant $tenant): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'tenant_owner', 'status' => 'active']);

        return $user;
    }

    public function test_changelog_page_shows_published_seed_entry(): void
    {
        $tenant = $this->createTenantWithActiveDomain('chg_pub');
        $user = $this->tenantOwner($tenant);
        $this->bindTenantFilamentContext($tenant);

        Livewire::actingAs($user)
            ->test(TenantProductChangelogPage::class)
            ->assertSuccessful()
            ->assertSee('Старт платформы RentBase для вашего бизнеса', false);
    }

    public function test_draft_entry_hidden_on_changelog_page(): void
    {
        $tenant = $this->createTenantWithActiveDomain('chg_draft');
        $user = $this->tenantOwner($tenant);
        $this->bindTenantFilamentContext($tenant);

        PlatformProductChangelogEntry::query()
            ->where('title', 'Старт платформы RentBase для вашего бизнеса')
            ->update(['is_published' => false]);

        Livewire::actingAs($user)
            ->test(TenantProductChangelogPage::class)
            ->assertSuccessful()
            ->assertDontSee('Старт платформы RentBase для вашего бизнеса', false);
    }

    public function test_ordering_newer_day_and_sort_weight_within_day(): void
    {
        $tenant = $this->createTenantWithActiveDomain('chg_order');
        $user = $this->tenantOwner($tenant);
        $this->bindTenantFilamentContext($tenant);

        $html = Livewire::actingAs($user)
            ->test(TenantProductChangelogPage::class)
            ->assertSuccessful()
            ->html();

        $this->assertMatchesRegularExpression(
            '/id="tenant-changelog-day-2026-04-05"[\s\S]*?Уведомления: правила, получатели, доставка[\s\S]*?SEO по умолчанию при запуске клиента/',
            $html,
            'Within 2026-04-05, higher sort_weight entry should appear before lower'
        );

        $posApr5 = strpos($html, 'tenant-changelog-day-2026-04-05');
        $posApr4 = strpos($html, 'tenant-changelog-day-2026-04-04');
        $this->assertNotFalse($posApr5);
        $this->assertNotFalse($posApr4);
        // Newer day first: PHPUnit assertLessThan($expected, $actual) asserts $actual < $expected.
        $this->assertLessThan($posApr4, $posApr5);
    }

    public function test_whats_new_page_url_is_internal_admin_path(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $url = TenantProductChangelogPage::getUrl();
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $this->assertStringContainsString('/admin/', $path);
        $this->assertStringContainsString('whats-new', $path);
    }
}
