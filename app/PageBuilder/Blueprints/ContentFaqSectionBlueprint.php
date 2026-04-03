<?php

namespace App\PageBuilder\Blueprints;

use App\PageBuilder\PageSectionCategory;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;

final class ContentFaqSectionBlueprint extends AbstractPageSectionBlueprint
{
    public function id(): string
    {
        return 'content_faq';
    }

    public function label(): string
    {
        return 'FAQ';
    }

    public function description(): string
    {
        return 'Вопросы и ответы для информационной страницы.';
    }

    public function icon(): string
    {
        return 'heroicon-o-chat-bubble-left-right';
    }

    public function category(): PageSectionCategory
    {
        return PageSectionCategory::HelpNotices;
    }

    public function defaultData(): array
    {
        return [
            'title' => 'Частые вопросы',
            'items' => [
                ['question' => '', 'answer' => ''],
            ],
        ];
    }

    public function formComponents(): array
    {
        return [
            TextInput::make('data_json.title')
                ->label('Заголовок секции')
                ->maxLength(255)
                ->columnSpanFull(),
            Repeater::make('data_json.items')
                ->label('Вопросы и ответы')
                ->schema([
                    TextInput::make('question')
                        ->label('Вопрос')
                        ->required()
                        ->maxLength(500)
                        ->columnSpanFull(),
                    RichEditor::make('answer')
                        ->label('Ответ')
                        ->required()
                        ->toolbarButtons([
                            'bold', 'italic', 'link', 'bulletList', 'orderedList', 'h3',
                        ])
                        ->columnSpanFull()
                        ->extraInputAttributes(['class' => 'tenant-page-section-rich-editor']),
                ])
                ->defaultItems(1)
                ->columnSpanFull(),
        ];
    }

    public function viewLogicalName(): string
    {
        return 'sections.content-faq';
    }

    public function previewSummary(array $data): string
    {
        $n = $this->countNestedList($data, 'items');

        return $n > 0 ? 'FAQ · '.$n.' '.self::pluralQuestions($n) : 'Нет вопросов';
    }

    private static function pluralQuestions(int $n): string
    {
        $m = $n % 100;
        $m10 = $n % 10;
        if ($m >= 11 && $m <= 19) {
            return 'вопросов';
        }

        return match ($m10) {
            1 => 'вопрос',
            2, 3, 4 => 'вопроса',
            default => 'вопросов',
        };
    }
}
