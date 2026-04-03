<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\PageSection;
use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Services\Tenancy\TenantMainMenuPages;
use App\Services\Tenancy\TenantPagePrimaryHtmlSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class TenantPageCmsAndMenuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    protected function getWithHost(string $host, string $path = '/'): TestResponse
    {
        $path = str_starts_with($path, '/') ? $path : '/'.$path;

        return $this->call('GET', 'http://'.$host.$path);
    }

    protected function createTenantSite(string $subdomain): Tenant
    {
        $tenant = Tenant::query()->create([
            'name' => 'T '.$subdomain,
            'slug' => $subdomain,
            'status' => 'active',
        ]);

        TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'host' => $subdomain.'.apex.test',
            'type' => TenantDomain::TYPE_SUBDOMAIN,
            'is_primary' => true,
            'status' => TenantDomain::STATUS_ACTIVE,
            'ssl_status' => TenantDomain::SSL_NOT_REQUIRED,
            'verified_at' => now(),
            'activated_at' => now(),
        ]);

        Cache::flush();

        return $tenant->fresh();
    }

    protected function seedMinimalHome(Tenant $tenant): Page
    {
        return Page::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Главная',
            'slug' => 'home',
            'template' => 'default',
            'status' => 'published',
            'published_at' => now(),
            'show_in_main_menu' => false,
            'main_menu_sort_order' => 0,
        ]);
    }

    public function test_menu_lists_only_published_with_flag(): void
    {
        $tenant = $this->createTenantSite('menufilter');
        $home = $this->seedMinimalHome($tenant);

        Page::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'В меню да',
            'slug' => 'in-menu-yes',
            'template' => 'default',
            'status' => 'published',
            'published_at' => now(),
            'show_in_main_menu' => true,
            'main_menu_sort_order' => 1,
        ]);

        Page::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'В меню нет',
            'slug' => 'in-menu-no',
            'template' => 'default',
            'status' => 'published',
            'published_at' => now(),
            'show_in_main_menu' => false,
            'main_menu_sort_order' => 0,
        ]);

        Page::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Скрыт с флагом',
            'slug' => 'hidden-flagged',
            'template' => 'default',
            'status' => 'hidden',
            'published_at' => null,
            'show_in_main_menu' => true,
            'main_menu_sort_order' => 0,
        ]);

        Page::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Черновик с флагом',
            'slug' => 'draft-flagged',
            'template' => 'default',
            'status' => 'draft',
            'published_at' => null,
            'show_in_main_menu' => true,
            'main_menu_sort_order' => 0,
        ]);

        $home->update([
            'show_in_main_menu' => true,
            'name' => 'Ложный пункт главной',
        ]);

        $this->getWithHost('menufilter.apex.test', '/')
            ->assertOk()
            ->assertSee('В меню да', false)
            ->assertDontSee('В меню нет', false)
            ->assertDontSee('Скрыт с флагом', false)
            ->assertDontSee('Черновик с флагом', false)
            ->assertDontSee('Ложный пункт главной', false);
    }

    public function test_home_slug_never_appears_in_menu_even_with_flag(): void
    {
        $tenant = $this->createTenantSite('homeexcl');
        $home = $this->seedMinimalHome($tenant);
        $home->update([
            'show_in_main_menu' => true,
            'name' => 'Дубль главной',
        ]);

        $items = app(TenantMainMenuPages::class)->menuItems($tenant);
        $this->assertTrue($items->isEmpty());
    }

    public function test_menu_service_uses_single_page_show_route(): void
    {
        $tenant = $this->createTenantSite('menuroute');
        Page::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Контактный лист',
            'slug' => 'contacts',
            'template' => 'default',
            'status' => 'published',
            'published_at' => now(),
            'show_in_main_menu' => true,
            'main_menu_sort_order' => 0,
        ]);

        $items = app(TenantMainMenuPages::class)->menuItems($tenant);
        $this->assertCount(1, $items);
        $this->assertSame(route('page.show', ['slug' => 'contacts']), $items->first()['url']);
    }

    public function test_cms_page_accessible_without_menu_flag(): void
    {
        $tenant = $this->createTenantSite('cmsnovisit');

        $page = Page::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Контакты',
            'slug' => 'contacts',
            'template' => 'default',
            'status' => 'published',
            'published_at' => now(),
            'show_in_main_menu' => false,
            'main_menu_sort_order' => 0,
        ]);

        PageSection::query()->create([
            'tenant_id' => $tenant->id,
            'page_id' => $page->id,
            'section_key' => 'main',
            'title' => 'main',
            'data_json' => ['content' => '<p>Тело</p>'],
            'sort_order' => 0,
            'is_visible' => true,
            'status' => 'published',
        ]);

        $this->getWithHost('cmsnovisit.apex.test', '/contacts')
            ->assertOk()
            ->assertSee('Тело', false);
    }

    public function test_primary_html_sync_writes_main_section(): void
    {
        $tenant = $this->createTenantSite('primsync');
        $page = Page::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Sync',
            'slug' => 'sync-page',
            'template' => 'default',
            'status' => 'published',
            'published_at' => now(),
        ]);

        app(TenantPagePrimaryHtmlSync::class)->sync($page, '<p>Primary</p>');

        $this->assertDatabaseHas('page_sections', [
            'page_id' => $page->id,
            'section_key' => 'main',
        ]);

        $row = PageSection::query()->where('page_id', $page->id)->where('section_key', 'main')->first();
        $this->assertSame('<p>Primary</p>', $row->data_json['content'] ?? null);
        $this->assertSame('rich_text', $row->section_type);
    }

    public function test_secondary_sections_query_excludes_main_for_non_home(): void
    {
        $tenant = $this->createTenantSite('rmexclude');
        $page = Page::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Обычная',
            'slug' => 'ordinary',
            'template' => 'default',
            'status' => 'published',
            'published_at' => now(),
        ]);

        PageSection::query()->create([
            'tenant_id' => $tenant->id,
            'page_id' => $page->id,
            'section_key' => 'main',
            'title' => 'Main',
            'data_json' => ['content' => 'x'],
            'sort_order' => 0,
            'is_visible' => true,
            'status' => 'published',
        ]);

        PageSection::query()->create([
            'tenant_id' => $tenant->id,
            'page_id' => $page->id,
            'section_key' => 'extra',
            'title' => 'Extra',
            'data_json' => ['content' => 'y'],
            'sort_order' => 1,
            'is_visible' => true,
            'status' => 'published',
        ]);

        $allKeys = $page->fresh()->sections()->pluck('section_key')->sort()->values()->all();
        $this->assertEqualsCanonicalizing(['extra', 'main'], $allKeys);

        $rmKeys = $page->fresh()->sections()->where('section_key', '!=', 'main')->pluck('section_key')->all();
        $this->assertSame(['extra'], $rmKeys);
    }
}
