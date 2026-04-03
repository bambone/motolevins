<?php

namespace App\PageBuilder\Blueprints;

use App\PageBuilder\PageSectionCategory;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

final class StructuredTextSectionBlueprint extends AbstractPageSectionBlueprint
{
    public function id(): string
    {
        return 'structured_text';
    }

    public function label(): string
    {
        return 'Структурированный текст';
    }

    public function description(): string
    {
        return 'Большой текстовый блок с форматированием, заголовками и списками.';
    }

    public function icon(): string
    {
        return 'heroicon-o-document-text';
    }

    public function category(): PageSectionCategory
    {
        return PageSectionCategory::PageContent;
    }

    public function defaultData(): array
    {
        return [
            'title' => null,
            'content' => '',
            'max_width' => 'prose',
        ];
    }

    public function formComponents(): array
    {
        return [
            TextInput::make('data_json.title')
                ->label('Заголовок (необязательно)')
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
            Select::make('data_json.max_width')
                ->label('Ширина контента')
                ->options([
                    'prose' => 'Узкая (читаемая колонка)',
                    'wide' => 'Шире',
                    'full' => 'На всю ширину',
                ])
                ->native(true)
                ->required(),
        ];
    }

    public function viewLogicalName(): string
    {
        return 'sections.structured-text';
    }

    public function previewSummary(array $data): string
    {
        $title = trim((string) ($data['title'] ?? ''));
        if ($title !== '') {
            return $this->stringPreview($data, 'title', 120);
        }
        $plain = strip_tags((string) ($data['content'] ?? ''));
        $plain = trim(preg_replace('/\s+/', ' ', $plain) ?? '');

        return $plain !== '' ? (strlen($plain) > 120 ? substr($plain, 0, 120).'…' : $plain) : 'Пустой текстовый блок';
    }
}
