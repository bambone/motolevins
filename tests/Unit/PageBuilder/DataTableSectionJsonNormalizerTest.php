<?php

namespace Tests\Unit\PageBuilder;

use App\PageBuilder\DataTableSectionJsonNormalizer;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DataTableSectionJsonNormalizerTest extends TestCase
{
    #[Test]
    public function legacy_list_cells_map_to_column_keys(): void
    {
        $k1 = 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa';
        $k2 = 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb';
        $out = DataTableSectionJsonNormalizer::normalizeForPersistence([
            'title' => 'T',
            'columns' => [
                ['key' => $k1, 'name' => 'A'],
                ['key' => $k2, 'name' => 'B'],
            ],
            'rows' => [
                ['cells' => [['value' => 'x'], ['value' => 'y']]],
            ],
        ]);

        $this->assertSame('x', $out['rows'][0]['cells'][$k1]['value']);
        $this->assertSame('y', $out['rows'][0]['cells'][$k2]['value']);
    }

    #[Test]
    public function dedupe_duplicate_column_keys_remaps_second_column_cells(): void
    {
        $k = 'cccccccc-cccc-cccc-cccc-cccccccccccc';
        $out = DataTableSectionJsonNormalizer::normalizeForPersistence([
            'columns' => [
                ['key' => $k, 'name' => 'First'],
                ['key' => $k, 'name' => 'Clone'],
            ],
            'rows' => [
                ['cells' => [$k => ['value' => 'only']]],
            ],
        ]);

        $keys = array_map(fn (array $c): string => (string) $c['key'], $out['columns']);
        $this->assertCount(2, $keys);
        $this->assertNotSame($keys[0], $keys[1]);
        $this->assertSame('only', $out['rows'][0]['cells'][$keys[0]]['value']);
        $this->assertSame('only', $out['rows'][0]['cells'][$keys[1]]['value']);
    }

    /**
     * Clone in the editor can yield two columns sharing one stable key; persistence must split keys and keep values.
     */
    #[Test]
    public function clone_scenario_duplicate_stable_key_becomes_two_columns_with_same_cell_values(): void
    {
        $shared = 'eeeeeeee-eeee-eeee-eeee-eeeeeeeeeeee';
        $out = DataTableSectionJsonNormalizer::normalizeForPersistence([
            'columns' => [
                ['key' => $shared, 'name' => 'Оригинал'],
                ['key' => $shared, 'name' => 'Клон'],
            ],
            'rows' => [
                ['cells' => [$shared => ['value' => 'cloned-value']]],
            ],
        ]);

        $k0 = (string) $out['columns'][0]['key'];
        $k1 = (string) $out['columns'][1]['key'];
        $this->assertNotSame($k0, $k1);
        $this->assertSame('cloned-value', $out['rows'][0]['cells'][$k0]['value']);
        $this->assertSame('cloned-value', $out['rows'][0]['cells'][$k1]['value']);
    }

    #[Test]
    public function assigns_keys_to_legacy_columns_without_keys(): void
    {
        $out = DataTableSectionJsonNormalizer::normalizeForPersistence([
            'columns' => [
                ['name' => 'A'],
                ['name' => 'B'],
            ],
            'rows' => [
                ['cells' => [['value' => '1'], ['value' => '2']]],
            ],
        ]);

        $this->assertCount(2, $out['columns']);
        $this->assertNotEmpty($out['columns'][0]['key']);
        $this->assertNotEmpty($out['columns'][1]['key']);
        $this->assertNotSame($out['columns'][0]['key'], $out['columns'][1]['key']);
        $k0 = $out['columns'][0]['key'];
        $k1 = $out['columns'][1]['key'];
        $this->assertSame('1', $out['rows'][0]['cells'][$k0]['value']);
        $this->assertSame('2', $out['rows'][0]['cells'][$k1]['value']);
    }

    #[Test]
    public function drops_scalar_repeater_items_keeps_valid_rows_and_columns(): void
    {
        $k1 = 'ffffffff-ffff-ffff-ffff-ffffffffffff';
        $k2 = '99999999-9999-9999-9999-999999999999';
        $out = DataTableSectionJsonNormalizer::hydrateForEditor([
            'columns' => [
                ['key' => $k1, 'name' => 'A'],
                404,
                ['key' => $k2, 'name' => 'B'],
            ],
            'rows' => [
                ['cells' => [$k1 => ['value' => 'x'], $k2 => ['value' => 'y']]],
                0,
            ],
        ]);

        $this->assertCount(2, $out['columns']);
        $this->assertCount(1, $out['rows']);
        $this->assertSame('x', $out['rows'][0]['cells'][$k1]['value'] ?? null);
        $this->assertSame('y', $out['rows'][0]['cells'][$k2]['value'] ?? null);
    }

    #[Test]
    public function column_key_has_non_empty_cells_detects_values(): void
    {
        $k = 'dddddddd-dddd-dddd-dddd-dddddddddddd';
        $this->assertTrue(DataTableSectionJsonNormalizer::columnKeyHasNonEmptyCells($k, [
            ['cells' => [$k => ['value' => ' hi ']]],
        ]));
        $this->assertFalse(DataTableSectionJsonNormalizer::columnKeyHasNonEmptyCells($k, [
            ['cells' => [$k => ['value' => '']]],
        ]));
    }

    #[Test]
    public function persistence_outputs_rows_as_zero_indexed_list(): void
    {
        $out = DataTableSectionJsonNormalizer::normalizeForPersistence([
            'columns' => [['name' => 'A']],
            'rows' => [
                'uuid-one' => ['cells' => []],
            ],
        ]);
        $this->assertTrue(array_is_list($out['rows']));
    }
}
