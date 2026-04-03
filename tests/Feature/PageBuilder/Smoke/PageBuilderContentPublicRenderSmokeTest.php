<?php

namespace Tests\Feature\PageBuilder\Smoke;

use App\Models\Page;
use App\Models\PageSection;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\AssertsHtmlMarkerOrder;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\Support\InteractsWithTenantSmokeHttp;
use Tests\Support\PageBuilderSmokeFixtures;
use Tests\TestCase;

/**
 * @group page-builder-smoke
 */
class PageBuilderContentPublicRenderSmokeTest extends TestCase
{
    use AssertsHtmlMarkerOrder;
    use CreatesTenantsWithDomains;
    use InteractsWithTenantSmokeHttp;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_rich_page_renders_all_eight_types_wrappers_order_and_main_first(): void
    {
        $tenant = $this->createTenantWithActiveDomain('pbsmoke-rich');
        $host = $this->tenancyHostForSlug('pbsmoke-rich');
        $page = $this->createPublishedPage($tenant, PageBuilderSmokeFixtures::SLUG_RICH, PageBuilderSmokeFixtures::PAGE_TITLE_RICH);
        $this->insertSectionRows($tenant, $page, array_merge(
            [PageBuilderSmokeFixtures::mainSectionAttributes()],
            PageBuilderSmokeFixtures::richExtraSectionRows()
        ));

        $res = $this->getTenantHtmlResponse($host, '/'.PageBuilderSmokeFixtures::SLUG_RICH);
        $res->assertOk();
        $html = $res->getContent();

        $this->assertStringContainsString(PageBuilderSmokeFixtures::PAGE_TITLE_RICH, $html);
        $this->assertMainMarkerBeforeFirstExtraSectionWrapper($html, PageBuilderSmokeFixtures::MARKER_MAIN);
        $this->assertSubstringsAppearInOrder($html, array_merge(
            [PageBuilderSmokeFixtures::PAGE_TITLE_RICH, PageBuilderSmokeFixtures::MARKER_MAIN],
            PageBuilderSmokeFixtures::richExtraMarkersInOrder()
        ));

        foreach (PageBuilderSmokeFixtures::markerByType() as $typeId => $marker) {
            $this->assertPageSectionWrapperContainsMarker($html, $typeId, $marker);
        }

        $res->assertSee('Smoke link', false);
    }

    public static function provideContentTypes(): array
    {
        return array_map(fn (string $id): array => [$id], PageBuilderSmokeFixtures::ORDERED_CONTENT_TYPES);
    }

    #[DataProvider('provideContentTypes')]
    public function test_single_extra_section_renders_wrapper_and_marker(string $typeId): void
    {
        $slug = 'pbsmoke-solo-'.str_replace('_', '-', $typeId);
        $tenant = $this->createTenantWithActiveDomain('pbsm-solo-'.str_replace('_', '', $typeId));
        $host = $this->tenancyHostForSlug((string) $tenant->slug);
        $page = $this->createPublishedPage($tenant, $slug, 'Solo '.$typeId);
        $marker = PageBuilderSmokeFixtures::markerByType()[$typeId];
        $this->insertSectionRows($tenant, $page, [
            PageBuilderSmokeFixtures::mainSectionAttributes(),
            PageBuilderSmokeFixtures::singleExtraRowForType($typeId),
        ]);

        $res = $this->getTenantHtmlResponse($host, '/'.$slug);
        $res->assertOk();
        $html = $res->getContent();

        $this->assertMainMarkerBeforeFirstExtraSectionWrapper($html, PageBuilderSmokeFixtures::MARKER_MAIN);
        $this->assertPageSectionWrapperContainsMarker($html, $typeId, $marker);
        $this->assertSubstringsAppearInOrder($html, [
            PageBuilderSmokeFixtures::MARKER_MAIN,
            $marker,
        ]);
    }

    public function test_edge_page_renders_without_breaking_wrappers_and_markers(): void
    {
        $tenant = $this->createTenantWithActiveDomain('pbsmoke-edge');
        $host = $this->tenancyHostForSlug('pbsmoke-edge');
        $page = $this->createPublishedPage($tenant, PageBuilderSmokeFixtures::SLUG_EDGE, PageBuilderSmokeFixtures::PAGE_TITLE_EDGE);
        $this->insertSectionRows($tenant, $page, array_merge(
            [PageBuilderSmokeFixtures::mainSectionAttributes()],
            PageBuilderSmokeFixtures::edgeExtraSectionRows()
        ));

        $res = $this->getTenantHtmlResponse($host, '/'.PageBuilderSmokeFixtures::SLUG_EDGE);
        $res->assertOk();
        $html = $res->getContent();

        $edgeMarkers = [
            PageBuilderSmokeFixtures::MARKER_STRUCTURED_TEXT.'-EDGE',
            PageBuilderSmokeFixtures::MARKER_TEXT_SECTION.'-EDGE',
            PageBuilderSmokeFixtures::MARKER_CONTENT_FAQ.'-EDGE',
            PageBuilderSmokeFixtures::MARKER_LIST_BLOCK.'-EDGE',
            PageBuilderSmokeFixtures::MARKER_INFO_CARDS.'-EDGE',
            PageBuilderSmokeFixtures::MARKER_CONTACTS_INFO.'-EDGE',
            PageBuilderSmokeFixtures::MARKER_DATA_TABLE.'-EDGE',
            PageBuilderSmokeFixtures::MARKER_NOTICE_BOX.'-EDGE',
        ];
        foreach (PageBuilderSmokeFixtures::ORDERED_CONTENT_TYPES as $i => $typeId) {
            $this->assertPageSectionWrapperContainsMarker($html, $typeId, $edgeMarkers[$i]);
        }
    }

    public function test_hidden_and_draft_sections_absent_from_public_html(): void
    {
        $tenant = $this->createTenantWithActiveDomain('pbsmoke-vis');
        $host = $this->tenancyHostForSlug('pbsmoke-vis');
        $page = $this->createPublishedPage($tenant, PageBuilderSmokeFixtures::SLUG_VISIBILITY, PageBuilderSmokeFixtures::PAGE_TITLE_VISIBILITY);
        $this->insertSectionRows($tenant, $page, array_merge(
            [PageBuilderSmokeFixtures::mainSectionAttributes()],
            [PageBuilderSmokeFixtures::publishedVisibleExtraRow()],
            PageBuilderSmokeFixtures::hiddenAndDraftRows()
        ));

        $res = $this->getTenantHtmlResponse($host, '/'.PageBuilderSmokeFixtures::SLUG_VISIBILITY);
        $res->assertOk();
        $html = $res->getContent();

        $this->assertStringContainsString(PageBuilderSmokeFixtures::MARKER_PUBLISHED_VISIBLE, $html);
        $this->assertStringNotContainsString(PageBuilderSmokeFixtures::MARKER_HIDDEN, $html);
        $this->assertStringNotContainsString(PageBuilderSmokeFixtures::MARKER_DRAFT, $html);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function insertSectionRows(Tenant $tenant, Page $page, array $rows): void
    {
        foreach ($rows as $row) {
            PageSection::query()->create(array_merge($row, [
                'tenant_id' => $tenant->id,
                'page_id' => $page->id,
            ]));
        }
    }

    private function createPublishedPage(Tenant $tenant, string $slug, string $name): Page
    {
        return Page::query()->create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'slug' => $slug,
            'template' => 'default',
            'status' => 'published',
            'published_at' => now(),
            'show_in_main_menu' => false,
            'main_menu_sort_order' => 0,
        ]);
    }
}
