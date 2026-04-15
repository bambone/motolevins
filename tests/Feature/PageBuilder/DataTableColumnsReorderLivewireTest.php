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
 * Regression: reordering teleported data_table columns must change display order but preserve cell values per column key.
 */
class DataTableColumnsReorderLivewireTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    private function bindTenantContext(Tenant $tenant): void
    {
        $host = $this->tenancyHostForSlug((string) $tenant->slug);
        $domain = TenantDomain::query()->where('tenant_id', $tenant->id)->firstOrFail();
        $this->app->instance(CurrentTenant::class, new CurrentTenant($tenant, $domain, false, $host));
    }

    public function test_reorder_columns_changes_key_order_and_preserves_cell_values(): void
    {
        $tenant = $this->createTenantWithActiveDomain('pb-dt-reorder', ['theme_key' => 'default']);
        $this->bindTenantContext($tenant);

        $page = Page::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Таблица',
            'slug' => 'table-reorder-pb',
            'template' => 'default',
            'status' => 'published',
            'published_at' => now(),
            'show_in_main_menu' => false,
            'main_menu_sort_order' => 0,
        ]);

        $k1 = '11111111-1111-1111-1111-111111111111';
        $k2 = '22222222-2222-2222-2222-222222222222';
        $k3 = '33333333-3333-3333-3333-333333333333';

        $section = PageSection::query()->create([
            'tenant_id' => $tenant->id,
            'page_id' => $page->id,
            'section_key' => 'tbl_reorder',
            'section_type' => 'data_table',
            'title' => 'Тарифы',
            'data_json' => [
                'title' => 'Сравнение',
                'columns' => [
                    ['key' => $k1, 'name' => 'Col A'],
                    ['key' => $k2, 'name' => 'Col B'],
                    ['key' => $k3, 'name' => 'Col C'],
                ],
                'rows' => [
                    [
                        'cells' => [
                            $k1 => ['value' => 'val-a'],
                            $k2 => ['value' => 'val-b'],
                            $k3 => ['value' => 'val-c'],
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
        $this->assertCount(3, $cols);
        $rows = $lw->get('sectionFormData.data_json.rows') ?? [];
        $this->assertSame('val-a', (string) (($rows[0]['cells'][$k1]['value'] ?? '')));
        $this->assertSame('val-b', (string) (($rows[0]['cells'][$k2]['value'] ?? '')));
        $this->assertSame('val-c', (string) (($rows[0]['cells'][$k3]['value'] ?? '')));

        $orderBefore = [];
        foreach ($cols as $item) {
            $this->assertIsArray($item);
            $orderBefore[] = (string) ($item['key'] ?? '');
        }
        $this->assertSame([$k1, $k2, $k3], $orderBefore);

        $repeaterKeys = array_keys($cols);
        $this->assertCount(3, $repeaterKeys);
        $middleItemKey = $repeaterKeys[1];

        $lw->call('mountAction', 'moveUp', ['item' => $middleItemKey], ['schemaComponent' => 'sectionEditor.data_json.columns'])
            ->call('callMountedAction');

        $colsAfter = $lw->get('sectionFormData.data_json.columns') ?? [];
        $this->assertCount(3, $colsAfter);

        $orderAfter = [];
        foreach ($colsAfter as $item) {
            $this->assertIsArray($item);
            $orderAfter[] = (string) ($item['key'] ?? '');
        }
        $this->assertSame([$k2, $k1, $k3], $orderAfter);

        $rowsAfter = $lw->get('sectionFormData.data_json.rows') ?? [];
        $this->assertSame('val-a', (string) (($rowsAfter[0]['cells'][$k1]['value'] ?? '')));
        $this->assertSame('val-b', (string) (($rowsAfter[0]['cells'][$k2]['value'] ?? '')));
        $this->assertSame('val-c', (string) (($rowsAfter[0]['cells'][$k3]['value'] ?? '')));
    }
}
