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
 * Deleting a data_table column with cell data uses confirmation; state must not change until confirm.
 */
class DataTableColumnDeleteConfirmLivewireTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    private function bindTenantContext(Tenant $tenant): void
    {
        $host = $this->tenancyHostForSlug((string) $tenant->slug);
        $domain = TenantDomain::query()->where('tenant_id', $tenant->id)->firstOrFail();
        $this->app->instance(CurrentTenant::class, new CurrentTenant($tenant, $domain, false, $host));
    }

    public function test_delete_column_with_cells_requires_confirm_before_state_changes(): void
    {
        $tenant = $this->createTenantWithActiveDomain('pb-dt-col-del', ['theme_key' => 'default']);
        $this->bindTenantContext($tenant);

        $page = Page::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Таблица',
            'slug' => 'table-col-del-pb',
            'template' => 'default',
            'status' => 'published',
            'published_at' => now(),
            'show_in_main_menu' => false,
            'main_menu_sort_order' => 0,
        ]);

        $k1 = 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa';
        $k2 = 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb';

        $section = PageSection::query()->create([
            'tenant_id' => $tenant->id,
            'page_id' => $page->id,
            'section_key' => 'tbl_del_col',
            'section_type' => 'data_table',
            'title' => 'Тарифы',
            'data_json' => [
                'title' => null,
                'columns' => [
                    ['key' => $k1, 'name' => 'Параметр'],
                    ['key' => $k2, 'name' => 'Значение'],
                ],
                'rows' => [
                    [
                        'cells' => [
                            $k1 => ['value' => 'rowlabel'],
                            $k2 => ['value' => 'has-data'],
                        ],
                    ],
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
        $repeaterKeys = array_keys($cols);
        $deleteRepeaterKey = $repeaterKeys[1];
        $deleteStableKey = (string) ($cols[$deleteRepeaterKey]['key'] ?? '');
        $this->assertSame($k2, $deleteStableKey);

        $snapshotBefore = $lw->get('sectionFormData.data_json');

        $lw->call('mountAction', 'delete', ['item' => $deleteRepeaterKey], ['schemaComponent' => 'sectionEditor.data_json.columns']);

        $this->assertEquals($snapshotBefore, $lw->get('sectionFormData.data_json'));

        $lw->call('callMountedAction');

        $colsAfter = $lw->get('sectionFormData.data_json.columns') ?? [];
        $this->assertCount(1, $colsAfter);
        $rowsAfter = $lw->get('sectionFormData.data_json.rows') ?? [];
        $cellsAfter = $rowsAfter[0]['cells'] ?? [];
        $this->assertArrayHasKey($k1, $cellsAfter);
        $this->assertArrayNotHasKey($k2, $cellsAfter);
    }
}
