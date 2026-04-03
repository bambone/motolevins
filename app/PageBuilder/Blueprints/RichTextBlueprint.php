<?php

namespace App\PageBuilder\Blueprints;

use App\PageBuilder\PageSectionCategory;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;

final class RichTextBlueprint extends AbstractPageSectionBlueprint
{
    public function id(): string
    {
        return 'rich_text';
    }

    public function label(): string
    {
        return 'Текстовый блок';
    }

    public function description(): string
    {
        return 'Заголовок и форматированный текст.';
    }

    public function icon(): string
    {
        return 'heroicon-o-document-text';
    }

    public function category(): PageSectionCategory
    {
        return PageSectionCategory::Content;
    }

    public function defaultData(): array
    {
        return [
            'heading' => '',
            'content' => '',
        ];
    }

    public function formComponents(): array
    {
        return [
            TextInput::make('data_json.heading')
                ->label('Заголовок секции')
                ->maxLength(255)
                ->columnSpanFull(),
            RichEditor::make('data_json.content')
                ->label('Текст')
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
        return 'sections.rich-text';
    }

    public function previewSummary(array $data): string
    {
        $h = $this->stringPreview($data, 'heading', 50);
        $c = strip_tags((string) ($data['content'] ?? ''));
        $c = trim(preg_replace('/\s+/', ' ', $c) ?? '');

        return $h !== '' ? $h : ($c !== '' ? substr($c, 0, 80).'…' : 'Пустой текст');
    }
}
