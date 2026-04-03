<?php

namespace App\PageBuilder\Blueprints;

use App\PageBuilder\PageSectionCategory;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

final class CtaBlueprint extends AbstractPageSectionBlueprint
{
    public function id(): string
    {
        return 'cta';
    }

    public function label(): string
    {
        return 'Призыв к действию (CTA)';
    }

    public function description(): string
    {
        return 'Заголовок, текст и кнопка со ссылкой.';
    }

    public function icon(): string
    {
        return 'heroicon-o-megaphone';
    }

    public function category(): PageSectionCategory
    {
        return PageSectionCategory::Conversion;
    }

    public function defaultData(): array
    {
        return [
            'heading' => '',
            'body' => '',
            'button_text' => '',
            'button_url' => '',
        ];
    }

    public function formComponents(): array
    {
        return [
            TextInput::make('data_json.heading')
                ->label('Заголовок')
                ->maxLength(255)
                ->columnSpanFull(),
            Textarea::make('data_json.body')
                ->label('Текст')
                ->rows(4)
                ->columnSpanFull(),
            TextInput::make('data_json.button_text')
                ->label('Текст кнопки')
                ->maxLength(120),
            TextInput::make('data_json.button_url')
                ->label('Ссылка')
                ->url()
                ->maxLength(2048),
        ];
    }

    public function viewLogicalName(): string
    {
        return 'sections.cta';
    }

    public function previewSummary(array $data): string
    {
        $h = $this->stringPreview($data, 'heading', 50);
        $b = $this->stringPreview($data, 'button_text', 40);

        return trim(($h !== '' ? $h : 'CTA').($b !== '' ? ' · '.$b : ''));
    }
}
