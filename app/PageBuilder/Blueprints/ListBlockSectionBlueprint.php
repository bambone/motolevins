<?php

namespace App\PageBuilder\Blueprints;

use App\PageBuilder\PageSectionCategory;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

final class ListBlockSectionBlueprint extends AbstractPageSectionBlueprint
{
    public function id(): string
    {
        return 'list_block';
    }

    public function label(): string
    {
        return 'Список / шаги';
    }

    public function description(): string
    {
        return 'Маркированный, нумерованный или пошаговый список.';
    }

    public function icon(): string
    {
        return 'heroicon-o-list-bullet';
    }

    public function category(): PageSectionCategory
    {
        return PageSectionCategory::StructureLists;
    }

    public function defaultData(): array
    {
        return [
            'title' => null,
            'variant' => 'bullets',
            'items' => [
                ['title' => '', 'text' => ''],
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
            Select::make('data_json.variant')
                ->label('Тип списка')
                ->options([
                    'bullets' => 'Маркированный',
                    'numbered' => 'Нумерованный',
                    'steps' => 'Шаги',
                ])
                ->native(true)
                ->required(),
            Repeater::make('data_json.items')
                ->label('Пункты')
                ->schema([
                    TextInput::make('title')
                        ->label('Подзаголовок пункта')
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Textarea::make('text')
                        ->label('Текст')
                        ->required()
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->defaultItems(1)
                ->columnSpanFull(),
        ];
    }

    public function viewLogicalName(): string
    {
        return 'sections.list-block';
    }

    public function previewSummary(array $data): string
    {
        $n = $this->countNestedList($data, 'items');
        $variant = (string) ($data['variant'] ?? 'bullets');
        $label = match ($variant) {
            'numbered' => 'Нумерованный',
            'steps' => 'Шаги',
            default => 'Список',
        };
        $unit = match ($variant) {
            'steps' => self::pluralSteps($n),
            default => self::pluralPoints($n),
        };

        return $n > 0 ? $label.' · '.$n.' '.$unit : $label.' · нет пунктов';
    }

    private static function pluralPoints(int $n): string
    {
        $m = $n % 100;
        $m10 = $n % 10;
        if ($m >= 11 && $m <= 19) {
            return 'пунктов';
        }

        return match ($m10) {
            1 => 'пункт',
            2, 3, 4 => 'пункта',
            default => 'пунктов',
        };
    }

    private static function pluralSteps(int $n): string
    {
        $m = $n % 100;
        $m10 = $n % 10;
        if ($m >= 11 && $m <= 19) {
            return 'шагов';
        }

        return match ($m10) {
            1 => 'шаг',
            2, 3, 4 => 'шага',
            default => 'шагов',
        };
    }
}
