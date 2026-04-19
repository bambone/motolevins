<?php

namespace App\PageBuilder\Blueprints\Expert;

use App\Filament\Forms\Components\TenantPublicImagePicker;
use App\Filament\Forms\Components\TenantPublicMediaPicker;
use App\Filament\Tenant\PageBuilder\TeleportedEditorRepeater;
use App\PageBuilder\PageSectionCategory;
use App\Support\RussianQuantity;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

final class ProcessStepsBlueprint extends ExpertSectionBlueprint
{
    public function id(): string
    {
        return 'process_steps';
    }

    public function label(): string
    {
        return 'Expert: Этапы работы';
    }

    public function description(): string
    {
        return 'Пошаговый процесс + боковой блок.';
    }

    public function icon(): string
    {
        return 'heroicon-o-list-bullet';
    }

    public function category(): PageSectionCategory
    {
        return PageSectionCategory::Content;
    }

    public function defaultData(): array
    {
        return [
            'section_heading' => '',
            'aside_title' => '',
            'aside_body' => '',
            'steps' => [],
            'aside_image_url' => '',
            'aside_video_url' => '',
            'aside_video_poster_url' => '',
        ];
    }

    public function formComponents(): array
    {
        return [
            TextInput::make('data_json.section_heading')->label('Заголовок')->maxLength(255)->columnSpanFull(),
            TenantPublicImagePicker::make('data_json.aside_image_url')
                ->label('Фото рядом с блоком (практика, машина)')
                ->uploadPublicSiteSubdirectory('site/page-builder/process-steps')
                ->helperText('Если задано видео ниже — фото скрывается.')
                ->columnSpanFull(),
            TenantPublicMediaPicker::make('data_json.aside_video_url')
                ->label('Видео рядом с блоком (MP4/WebM)')
                ->mediaType(TenantPublicMediaPicker::MEDIA_VIDEO)
                ->maxLength(2048)
                ->uploadPublicSiteSubdirectory('site/page-builder/process-steps')
                ->helperText('Если задано, показывается вместо фото.')
                ->columnSpanFull(),
            TenantPublicImagePicker::make('data_json.aside_video_poster_url')
                ->label('Постер для видео')
                ->uploadPublicSiteSubdirectory('site/page-builder/process-steps')
                ->columnSpanFull(),
            TextInput::make('data_json.aside_title')->label('Боковой блок — заголовок')->maxLength(255),
            Textarea::make('data_json.aside_body')->label('Боковой блок — текст')->rows(3)->columnSpanFull(),
            TeleportedEditorRepeater::make('data_json.steps')
                ->label('Шаги')
                ->schema([
                    TextInput::make('title')->label('Заголовок')->required()->maxLength(255),
                    Textarea::make('body')->label('Текст')->rows(2)->columnSpanFull(),
                ])
                ->defaultItems(3)
                ->minItems(1)
                ->addActionLabel('Добавить шаг')
                ->columnSpanFull(),
        ];
    }

    public function viewLogicalName(): string
    {
        return 'sections.process_steps';
    }

    public function previewSummary(array $data): string
    {
        $n = $this->countNestedList($data, 'steps');

        if ($n <= 0) {
            return 'Нет шагов';
        }

        return $n.' '.RussianQuantity::fewMany($n, 'шаг', 'шага', 'шагов');
    }
}
