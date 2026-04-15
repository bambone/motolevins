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

class DataTableSectionLivewireHydrateTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    private function bindTenantContext(Tenant $tenant): void
    {
        $host = $this->tenancyHostForSlug((string) $tenant->slug);
        $domain = TenantDomain::query()->where('tenant_id', $tenant->id)->firstOrFail();
        $this->app->instance(CurrentTenant::class, new CurrentTenant($tenant, $domain, false, $host));
    }

    public function test_start_edit_normalizes_legacy_table_data_in_form_state(): void
    {
        $tenant = $this->createTenantWithActiveDomain('pb-dt-hydrate', ['theme_key' => 'default']);
        $this->bindTenantContext($tenant);

        $page = Page::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Таблица',
            'slug' => 'table-pb',
            'template' => 'default',
            'status' => 'published',
            'published_at' => now(),
            'show_in_main_menu' => false,
            'main_menu_sort_order' => 0,
        ]);

        $section = PageSection::query()->create([
            'tenant_id' => $tenant->id,
            'page_id' => $page->id,
            'section_key' => 'tbl_1',
            'section_type' => 'data_table',
            'title' => 'Тарифы',
            'data_json' => [
                'title' => 'Сравнение',
                'columns' => [
                    ['name' => 'A'],
                    ['name' => 'B'],
                ],
                'rows' => [
                    ['cells' => [['value' => '1'], ['value' => '2']]],
                ],
            ],
            'sort_order' => 10,
            'is_visible' => true,
            'status' => 'published',
        ]);

        $lw = Livewire::test(PageSectionsBuilder::class, ['record' => $page->fresh()])
            ->call('startEdit', $section->id);

        $cols = $lw->get('sectionFormData.data_json.columns') ?? [];
        $this->assertCount(2, $cols);
        $this->assertNotEmpty($cols[0]['key'] ?? null);
        $this->assertNotEmpty($cols[1]['key'] ?? null);
        $this->assertNotSame($cols[0]['key'], $cols[1]['key']);

        $k0 = (string) $cols[0]['key'];
        $k1 = (string) $cols[1]['key'];
        $rows = $lw->get('sectionFormData.data_json.rows') ?? [];
        $this->assertCount(1, $rows);
        $firstRow = $rows[array_key_first($rows)] ?? [];
        $cells = $firstRow['cells'] ?? [];
        $this->assertSame('1', (string) (($cells[$k0]['value'] ?? $cells[$k0] ?? '')));
        $this->assertSame('2', (string) (($cells[$k1]['value'] ?? $cells[$k1] ?? '')));
    }
}
