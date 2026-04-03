<?php

namespace App\PageBuilder\Blueprints;

use App\PageBuilder\PageSectionCategory;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;

final class TextSectionBlueprint extends AbstractPageSectionBlueprint
{
    public function id(): string
    {
        return 'text_section';
    }

    public function label(): string
    {
        return 'Текстовый раздел';
    }

    public function description(): string
    {
        return 'Заголовок и текст отдельного смыслового блока.';
    }

    public function icon(): string
    {
        return 'heroicon-o-queue-list';
    }

    public function category(): PageSectionCategory
    {
        return PageSectionCategory::PageContent;
    }

    public function defaultData(): array
    {
        return [
            'title' => 'Новый раздел',
            'content' => '',
        ];
    }

    public function formComponents(): array
    {
        return [
            TextInput::make('data_json.title')
                ->label('Заголовок раздела')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
            RichEditor::make('data_json.content')
                ->label('Текст')
                ->required()
                ->toolbarButtons([
                    'bold', 'italic', 'underline', 'strike', 'link',
                    'bulletList', 'orderedList', 'h2', 'h3', 'blockquote',
                ])
                ->columnSpanFull()
                ->extraInputAttributes(['class' => 'tenant-page-section-rich-editor']),
        ];
    }

    public function viewLogicalName(): string
    {
        return 'sections.text-section';
    }

    public function previewSummary(array $data): string
    {
        $t = $this->stringPreview($data, 'title', 80);
        $plain = strip_tags((string) ($data['content'] ?? ''));
        $plain = trim(preg_replace('/\s+/', ' ', $plain) ?? '');
        $snippet = $plain !== '' ? (strlen($plain) > 60 ? substr($plain, 0, 60).'…' : $plain) : '';

        return trim($t.($snippet !== '' ? ' · '.$snippet : ''));
    }
}
