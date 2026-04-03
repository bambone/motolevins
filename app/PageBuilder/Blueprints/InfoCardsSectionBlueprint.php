<?php

namespace App\PageBuilder\Blueprints;

use App\PageBuilder\PageSectionCategory;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

final class InfoCardsSectionBlueprint extends AbstractPageSectionBlueprint
{
    public function id(): string
    {
        return 'info_cards';
    }

    public function label(): string
    {
        return 'Информационные карточки';
    }

    public function description(): string
    {
        return 'Сетка карточек с иконкой, заголовком и описанием.';
    }

    public function icon(): string
    {
        return 'heroicon-o-squares-2x2';
    }

    public function category(): PageSectionCategory
    {
        return PageSectionCategory::InfoBlocks;
    }

    public function defaultData(): array
    {
        return [
            'title' => null,
            'columns' => 3,
            'items' => [
                ['icon' => 'check', 'title' => '', 'text' => ''],
            ],
        ];
    }

    public function formComponents(): array
    {
        return [
            TextInput::make('data_json.title')
                ->label('Заголовок секции (необязательно)')
                ->maxLength(255)
                ->columnSpanFull(),
            Select::make('data_json.columns')
                ->label('Колонок в ряд')
                ->options([2 => '2', 3 => '3', 4 => '4'])
                ->native(true)
                ->required(),
            Repeater::make('data_json.items')
                ->label('Карточки')
                ->schema([
                    Select::make('icon')
                        ->label('Иконка')
                        ->options([
                            'check' => 'Галочка',
                            'shield' => 'Щит',
                            'clock' => 'Часы',
                            'phone' => 'Телефон',
                            'map' => 'Карта',
                            'star' => 'Звезда',
                            'info' => 'Инфо',
                        ])
                        ->native(true)
                        ->required(),
                    TextInput::make('title')
                        ->label('Заголовок')
                        ->required()
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
        return 'sections.info-cards';
    }

    public function previewSummary(array $data): string
    {
        $n = $this->countNestedList($data, 'items');

        return $n > 0 ? 'Карточки · '.$n : 'Нет карточек';
    }
}
