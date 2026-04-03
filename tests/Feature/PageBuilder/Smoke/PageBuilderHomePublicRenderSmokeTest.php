<?php

namespace Tests\Feature\PageBuilder\Smoke;

use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\Support\InteractsWithTenantSmokeHttp;
use Tests\Support\PageBuilderSmokeFixtures;
use Tests\TestCase;

/**
 * Smoke stability of tenant home template (not full landing coverage). Empty motorcycle catalog is valid.
 *
 * @group page-builder-smoke
 */
class PageBuilderHomePublicRenderSmokeTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use InteractsWithTenantSmokeHttp;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_home_renders_200_and_smoke_hero_marker(): void
    {
        $tenant = $this->createTenantWithActiveDomain('pbsmoke-home');
        $host = $this->tenancyHostForSlug('pbsmoke-home');

        $home = Page::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Главная',
            'slug' => 'home',
            'template' => 'default',
            'status' => 'published',
            'published_at' => now(),
            'show_in_main_menu' => false,
            'main_menu_sort_order' => 0,
        ]);

        foreach (PageBuilderSmokeFixtures::homeSectionRows() as $row) {
            PageSection::query()->create(array_merge($row, [
                'tenant_id' => $tenant->id,
                'page_id' => $home->id,
            ]));
        }

        $res = $this->getTenantHtmlResponse($host, '/');
        $res->assertOk();
        $res->assertSee(PageBuilderSmokeFixtures::MARKER_HOME_HERO, false);
    }
}
