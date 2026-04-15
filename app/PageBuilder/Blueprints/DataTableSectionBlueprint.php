<?php

namespace App\PageBuilder\Blueprints;

use App\Filament\Tenant\PageBuilder\SectionAdminSummary;
use App\Filament\Tenant\PageBuilder\TeleportedEditorRepeater;
use App\Models\PageSection;
use App\PageBuilder\DataTableSectionJsonNormalizer;
use App\PageBuilder\PageSectionCategory;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

final class DataTableSectionBlueprint extends AbstractPageSectionBlueprint
{
    private static bool $syncing = false;

    public function id(): string
    {
        return 'data_table';
    }

    public function label(): string
    {
        return 'Таблица';
    }

    public function description(): string
    {
        return 'Тарифы, условия, параметры в табличном виде.';
    }

    public function icon(): string
    {
        return 'heroicon-o-table-cells';
    }

    public function category(): PageSectionCategory
    {
        return PageSectionCategory::StructureLists;
    }

    public function defaultData(): array
    {
        $k1 = (string) Str::uuid();
        $k2 = (string) Str::uuid();

        return [
            'title' => null,
            'columns' => [
                ['key' => $k1, 'name' => 'Параметр'],
                ['key' => $k2, 'name' => 'Значение'],
            ],
            'rows' => [
                [
                    'cells' => [
                        $k1 => ['value' => ''],
                        $k2 => ['value' => ''],
                    ],
                ],
            ],
        ];
    }

    public function formComponents(): array
    {
        return [
            TextInput::make('data_json.title')
                ->label('Заголовок (необязательно)')
                ->maxLength(255)
                ->columnSpanFull(),
            $this->columnsRepeater(),
            $this->rowsRepeater(),
        ];
    }

    public function viewLogicalName(): string
    {
        return 'sections.data-table';
    }

    public function previewSummary(array $data): string
    {
        $rows = $data['rows'] ?? [];

        return is_array($rows) ? 'Таблица · '.count($rows).' '.self::pluralRows(count($rows)) : 'Таблица';
    }

    public function adminSummary(PageSection $section): SectionAdminSummary
    {
        $data = is_array($section->data_json) ? $section->data_json : [];
        $label = $this->label();
        $listTitle = trim((string) ($section->title ?? ''));
        $displayTitle = $listTitle !== '' ? $listTitle : $label;
        $rows = $data['rows'] ?? [];
        $n = is_array($rows) ? count($rows) : 0;
        $lines = [$n > 0 ? $n.' '.self::pluralRows($n) : 'Нет строк'];
        $key = trim((string) ($section->section_key ?? ''));
        $displaySubtitle = $key !== '' ? $key.' · '.$label : $label;

        return new SectionAdminSummary(
            displayTitle: $displayTitle,
            displaySubtitle: $displaySubtitle,
            summaryLines: $lines,
            badges: ['Таблица'],
            meta: ['row_count' => (string) $n],
            isEmpty: $n === 0,
            warning: $n === 0 ? 'В таблице нет строк' : null,
            primaryHeadline: null,
            channels: [],
        );
    }

    private function columnsRepeater(): TeleportedEditorRepeater
    {
        return TeleportedEditorRepeater::make('data_json.columns')
            ->label('Колонки (слева направо)')
            ->schema([
                Hidden::make('key')
                    ->default(fn () => (string) Str::uuid())
                    ->dehydrated(),
                TextInput::make('name')
                    ->label('Заголовок колонки')
                    ->required()
                    ->maxLength(255),
            ])
            ->defaultItems(2)
            ->minItems(1)
            ->cloneable()
            ->addActionLabel('Добавить колонку')
            ->itemLabel(fn (array $state): string => (string) ($state['name'] ?? 'Колонка'))
            ->deleteAction(fn (Action $action): Action => $this->columnDeleteAction($action))
            ->afterStateUpdated(function (?array $state, Set $set, Get $get): void {
                if (! is_array($state)) {
                    return;
                }
                self::syncTable($set, $get);
            })
            ->columnSpanFull();
    }

    private function columnDeleteAction(Action $action): Action
    {
        return TeleportedEditorRepeater::withFullLivewireRenderAfter($action)
            ->requiresConfirmation()
            ->modalHeading('Удалить колонку?')
            ->modalDescription(function (Get $get, Repeater $component, ?array $arguments): HtmlString {
                $items = $component->getState();
                $itemId = is_array($arguments) ? (string) ($arguments['item'] ?? '') : '';
                $col = is_array($items) && $itemId !== '' ? ($items[$itemId] ?? []) : [];
                $colKey = is_array($col) ? (string) ($col['key'] ?? '') : '';
                $rows = $get('data_json.rows') ?? [];
                $rows = is_array($rows) ? $rows : [];
                $has = DataTableSectionJsonNormalizer::columnKeyHasNonEmptyCells($colKey, $rows);
                $base = 'Колонка и все значения этой колонки в строках будут удалены без возможности восстановления.';
                $extra = $has ? ' В этой колонке есть заполненные ячейки.' : '';

                return new HtmlString('<p>'.e($base.$extra).'</p>');
            });
    }

    private function rowsRepeater(): TeleportedEditorRepeater
    {
        return TeleportedEditorRepeater::make('data_json.rows')
            ->label('Строки')
            ->schema(function (Get $get): array {
                $columns = $get('data_json.columns') ?? [];
                if (! is_array($columns)) {
                    return [];
                }
                $fields = [];
                foreach ($columns as $col) {
                    if (! is_array($col)) {
                        continue;
                    }
                    $colKey = trim((string) ($col['key'] ?? ''));
                    if ($colKey === '') {
                        continue;
                    }
                    $label = (string) ($col['name'] ?? 'Колонка');
                    $fields[] = TextInput::make('cells.'.$colKey.'.value')
                        ->label($label)
                        ->maxLength(2000);
                }

                return $fields;
            })
            ->defaultItems(1)
            ->addActionLabel('Добавить строку')
            ->itemLabel(fn (array $state): string => 'Строка таблицы')
            ->afterStateUpdated(function (?array $state, Set $set, Get $get): void {
                if (! is_array($state)) {
                    return;
                }
                self::syncTable($set, $get);
            })
            ->columnSpanFull();
    }

    /**
     * @internal
     */
    private static function syncTable(Set $set, Get $get): void
    {
        if (self::$syncing) {
            return;
        }
        self::$syncing = true;
        try {
            $out = DataTableSectionJsonNormalizer::syncForEditor([
                'title' => $get('data_json.title'),
                'columns' => $get('data_json.columns') ?? [],
                'rows' => $get('data_json.rows') ?? [],
            ]);
            $set('data_json.columns', $out['columns']);
            $set('data_json.rows', $out['rows']);
        } finally {
            self::$syncing = false;
        }
    }

    private static function pluralRows(int $n): string
    {
        $m = $n % 100;
        $m10 = $n % 10;
        if ($m >= 11 && $m <= 19) {
            return 'строк';
        }

        return match ($m10) {
            1 => 'строка',
            2, 3, 4 => 'строки',
            default => 'строк',
        };
    }
}
