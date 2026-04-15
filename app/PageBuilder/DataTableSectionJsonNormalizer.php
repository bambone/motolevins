<?php

namespace App\PageBuilder;

use App\PageBuilder\Blueprints\DataTableSectionBlueprint;
use Illuminate\Support\Str;

/**
 * Keeps {@see DataTableSectionBlueprint} column keys and row cells maps in sync.
 *
 * Intentionally fixed shape: {@see normalizeInternal()} persists only `title`,
 * `columns` as a list of `key` + `name`, and `rows` with
 * `cells` as a map `columnKey => ['value' => string]`. Any future fields
 * (column width, alignment, row notes, etc.) must be added explicitly to this normalizer
 * (prefer a single allowlist of keys per column/row) or they will be dropped on save.
 *
 * @phpstan-type ColumnRow array{key: string, name: string}
 * @phpstan-type RowData array{cells: array<string, array{value: string}>}
 */
final class DataTableSectionJsonNormalizer
{
    /**
     * Avoid {@see array_replace_recursive} between default and stored `data_json` for tables: it corrupts
     * list-shaped `cells` merged with keyed defaults.
     *
     * @param  array<string, mixed>  $defaults
     * @param  array<string, mixed>  $existing
     * @return array{title: mixed, columns: mixed, rows: mixed}
     */
    public static function shallowBaseForMerge(array $defaults, array $existing): array
    {
        return [
            'title' => array_key_exists('title', $existing) ? $existing['title'] : ($defaults['title'] ?? null),
            'columns' => is_array($existing['columns'] ?? null) ? $existing['columns'] : ($defaults['columns'] ?? []),
            'rows' => is_array($existing['rows'] ?? null) ? $existing['rows'] : ($defaults['rows'] ?? []),
        ];
    }

    /**
     * Full normalize for persistence (rows as JSON array,0..n).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizeForPersistence(array $data): array
    {
        $normalized = self::normalizeInternal($data);

        $rows = $normalized['rows'] ?? [];
        if (! is_array($rows)) {
            $rows = [];
        }
        $normalized['rows'] = array_is_list($rows) ? $rows : array_values($rows);

        return $normalized;
    }

    /**
     * Editor sync: same as internal normalize but preserves repeater UUID keys for rows when present.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function syncForEditor(array $data): array
    {
        return self::normalizeInternal($data, preserveRowKeys: true);
    }

    /**
     * Legacy + keyed: load from DB / merge defaults.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function hydrateForEditor(array $data): array
    {
        return self::syncForEditor($data);
    }

    /**
     * Normalizes to the fixed table shape described in the class docblock.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function normalizeInternal(array $data, bool $preserveRowKeys = false): array
    {
        $title = $data['title'] ?? null;
        $columns = $data['columns'] ?? [];
        $rows = $data['rows'] ?? [];

        if (! is_array($columns)) {
            $columns = [];
        }
        if (! is_array($rows)) {
            $rows = [];
        }

        $rowsWereList = array_is_list($rows);
        $columnsWereList = array_is_list($columns);

        // Filament repeaters expect each item to be an array; legacy/corrupt JSON may contain scalars (500 on getItemLabel).
        $columns = self::repeaterItemsArraysOnly($columns);
        $rows = self::repeaterItemsArraysOnly($rows);

        if ($columnsWereList) {
            $columns = array_values($columns);
        }
        if ($rowsWereList) {
            $rows = array_values($rows);
        }

        $columnsList = self::columnsWithKeysAssigned($columns);
        $rowsWork = self::rowsToAssocWorkArray($rows);

        [$columnsList, $rowsWork] = self::dedupeColumnKeysAndRemapRows($columnsList, $rowsWork);

        $columnKeys = self::orderedColumnKeys($columnsList);
        $rowsAssoc = self::normalizeRowsCellsAssoc($rowsWork, $columnKeys);

        if ($preserveRowKeys && $rowsWereList) {
            $rowsOut = array_values($rowsAssoc);
        } elseif ($preserveRowKeys) {
            $rowsOut = $rowsAssoc;
        } else {
            $rowsOut = array_values($rowsAssoc);
        }

        return [
            'title' => $title,
            'columns' => array_values($columnsList),
            'rows' => $rowsOut,
        ];
    }

    /**
     * @param  array<int|string, mixed>  $columns
     * @return list<ColumnRow>
     */
    public static function columnsWithKeysAssigned(array $columns): array
    {
        $out = [];
        foreach ($columns as $item) {
            if (! is_array($item)) {
                continue;
            }
            $name = isset($item['name']) ? trim((string) $item['name']) : '';
            $key = isset($item['key']) ? trim((string) $item['key']) : '';
            if ($key === '' || ! self::isLikelyUuid($key)) {
                $key = (string) Str::uuid();
            }
            $out[] = ['key' => $key, 'name' => $name];
        }

        return $out;
    }

    /**
     * @param  list<ColumnRow>  $columns
     * @param  array<string, RowData>  $rows
     * @return array{0: list<ColumnRow>, 1: array<string, RowData>}
     */
    public static function dedupeColumnKeysAndRemapRows(array $columns, array $rows): array
    {
        $seen = [];
        $columnsOut = [];
        foreach ($columns as $col) {
            $k = (string) ($col['key'] ?? '');
            $name = (string) ($col['name'] ?? '');
            if ($k === '') {
                $k = (string) Str::uuid();
            }
            if (! isset($seen[$k])) {
                $seen[$k] = true;
                $columnsOut[] = ['key' => $k, 'name' => $name];

                continue;
            }
            $newK = (string) Str::uuid();
            $columnsOut[] = ['key' => $newK, 'name' => $name];
            foreach ($rows as $uuid => $row) {
                if (! is_array($row)) {
                    continue;
                }
                $cells = $row['cells'] ?? [];
                if (! is_array($cells)) {
                    $cells = [];
                }
                $copyVal = self::extractScalarCellValue($cells[$k] ?? null);
                $cells[$newK] = ['value' => $copyVal];
                $rows[$uuid]['cells'] = $cells;
            }
        }

        return [$columnsOut, $rows];
    }

    /**
     * @param  list<ColumnRow>  $columns
     * @return list<string>
     */
    public static function orderedColumnKeys(array $columns): array
    {
        $keys = [];
        foreach ($columns as $col) {
            $k = (string) ($col['key'] ?? '');
            if ($k !== '') {
                $keys[] = $k;
            }
        }

        return $keys;
    }

    /**
     * @param  array<int|string, mixed>  $rows
     * @return array<string, RowData>
     */
    private static function rowsToAssocWorkArray(array $rows): array
    {
        $out = [];
        if (array_is_list($rows)) {
            foreach ($rows as $i => $row) {
                if (! is_array($row)) {
                    continue;
                }
                $cellsRaw = $row['cells'] ?? [];
                $out['legacy-row-'.$i] = ['cells' => is_array($cellsRaw) ? $cellsRaw : []];
            }

            return $out;
        }
        foreach ($rows as $uuid => $row) {
            if (! is_array($row)) {
                continue;
            }
            $cellsRaw = $row['cells'] ?? [];
            $key = is_string($uuid) && $uuid !== '' ? $uuid : (string) Str::uuid();
            $out[$key] = ['cells' => is_array($cellsRaw) ? $cellsRaw : []];
        }

        return $out;
    }

    /**
     * @param  array<string, RowData>  $rows
     * @param  list<string>  $columnKeys
     * @return array<string, RowData>
     */
    private static function normalizeRowsCellsAssoc(array $rows, array $columnKeys): array
    {
        $outAssoc = [];
        foreach ($rows as $uuid => $row) {
            $cellsRaw = is_array($row) && is_array($row['cells'] ?? null) ? $row['cells'] : [];
            $cells = self::normalizeCellsMap($cellsRaw, $columnKeys);
            $outAssoc[$uuid] = ['cells' => $cells];
        }

        return $outAssoc;
    }

    /**
     * @param  array<string|int, mixed>  $cells
     * @param  list<string>  $columnKeys
     * @return array<string, array{value: string}>
     */
    public static function normalizeCellsMap(array $cells, array $columnKeys): array
    {
        $map = [];

        if (array_is_list($cells) && $columnKeys !== []) {
            foreach ($columnKeys as $i => $colKey) {
                $item = $cells[$i] ?? null;
                $map[$colKey] = ['value' => self::extractScalarCellValue($item)];
            }
        } else {
            foreach ($cells as $k => $item) {
                $key = is_string($k) ? $k : '';
                if ($key === '' || ! in_array($key, $columnKeys, true)) {
                    continue;
                }
                $map[$key] = ['value' => self::extractScalarCellValue($item)];
            }
        }

        foreach ($columnKeys as $colKey) {
            if (! array_key_exists($colKey, $map)) {
                $map[$colKey] = ['value' => ''];
            }
        }

        foreach (array_keys($map) as $k) {
            if (! in_array($k, $columnKeys, true)) {
                unset($map[$k]);
            }
        }

        return $map;
    }

    /**
     * @param  array<string|int, RowData|array<string, mixed>>  $rows
     */
    public static function columnKeyHasNonEmptyCells(string $columnKey, array $rows): bool
    {
        if ($columnKey === '') {
            return false;
        }
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $cells = $row['cells'] ?? [];
            if (! is_array($cells)) {
                continue;
            }
            $val = $cells[$columnKey] ?? null;
            if (self::extractScalarCellValue($val) !== '') {
                return true;
            }
        }

        return false;
    }

    private static function isLikelyUuid(string $key): bool
    {
        return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $key);
    }

    /**
     * @param  array<int|string, mixed>  $items
     * @return array<int|string, mixed>
     */
    private static function repeaterItemsArraysOnly(array $items): array
    {
        $out = [];
        foreach ($items as $key => $item) {
            if (is_array($item)) {
                $out[$key] = $item;
            }
        }

        return $out;
    }

    public static function extractScalarCellValue(mixed $item): string
    {
        if (is_array($item)) {
            return trim((string) ($item['value'] ?? ''));
        }
        if (is_string($item) || is_numeric($item)) {
            return trim((string) $item);
        }

        return '';
    }
}
