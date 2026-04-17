<?php

namespace Tests\Feature\PageBuilder;

use App\Livewire\Tenant\PageSectionsBuilder;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Tenant\CurrentTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

/**
 * Контракт DOM: корень slide-over редактора всегда отдаёт data-setup-editor-section-id
 * (число или пустая строка для нового блока) — см. tenant-admin-site-setup.js getOpenEditorSectionIdFromDom().
 */
class PageSectionsBuilderEditorDomContractTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    private function bindTenantContext(Tenant $tenant): void
    {
        $host = $this->tenancyHostForSlug((string) $tenant->slug);
        $domain = TenantDomain::query()->where('tenant_id', $tenant->id)->firstOrFail();
        $this->app->instance(CurrentTenant::class, new CurrentTenant($tenant, $domain, false, $host));
    }

    public function test_editor_root_includes_numeric_data_setup_editor_section_id_when_editing_existing_block(): void
    {
        $tenant = $this->createTenantWithActiveDomain('pb-editor-contract');
        $this->bindTenantContext($tenant);

        $page = Page::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Контракт DOM',
            'slug' => 'pb-editor-contract-page',
            'template' => 'default',
            'status' => 'published',
            'published_at' => now(),
            'show_in_main_menu' => false,
            'main_menu_sort_order' => 0,
        ]);

        $section = PageSection::query()->create([
            'tenant_id' => $tenant->id,
            'page_id' => $page->id,
            'section_key' => 'text_section_contract',
            'section_type' => 'text_section',
            'title' => 'Block',
            'data_json' => ['title' => 'T', 'content' => '<p>x</p>'],
            'sort_order' => 10,
            'is_visible' => true,
            'status' => 'published',
        ]);

        $html = Livewire::test(PageSectionsBuilder::class, ['record' => $page->fresh()])
            ->call('startEdit', $section->id)
            ->html();

        $this->assertStringContainsString('page-sections-builder-editor', $html);
        $this->assertStringContainsString(
            'data-setup-editor-section-id="'.(string) $section->id.'"',
            $html,
            'Guided setup JS опирается на этот атрибут для сопоставления открытого редактора с шагом.',
        );
    }

    public function test_editor_root_includes_empty_data_setup_editor_section_id_when_adding_new_block(): void
    {
        $tenant = $this->createTenantWithActiveDomain('pb-editor-contract-new');
        $this->bindTenantContext($tenant);

        $page = Page::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Новый блок',
            'slug' => 'pb-editor-contract-new-page',
            'template' => 'default',
            'status' => 'published',
            'published_at' => now(),
            'show_in_main_menu' => false,
            'main_menu_sort_order' => 0,
        ]);

        $html = Livewire::test(PageSectionsBuilder::class, ['record' => $page->fresh()])
            ->call('startAdd', 'text_section')
            ->html();

        $this->assertStringContainsString('page-sections-builder-editor', $html);
        $this->assertMatchesRegularExpression(
            '/data-setup-editor-section-id=""/',
            $html,
            'Для нового блока id ещё нет — атрибут обязан быть пустым, но присутствовать.',
        );
    }
}
