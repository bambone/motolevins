<?php

namespace App\PageBuilder\Blueprints;

use App\Filament\Forms\Components\TenantPublicImagePicker;
use App\Filament\Tenant\PageBuilder\SectionAdminSummary;
use App\Filament\Tenant\PageBuilder\TeleportedEditorRepeater;
use App\Models\PageSection;
use App\PageBuilder\PageSectionCategory;
use App\Rules\CmsHrefRule;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

final class HeroBlueprint extends AbstractPageSectionBlueprint
{
    public function id(): string
    {
        return 'hero';
    }

    public function label(): string
    {
        return 'Hero';
    }

    public function description(): string
    {
        return 'Главный баннер: видео (постер + источник), тексты, опционально фон-картинка для других шаблонов.';
    }

    public function icon(): string
    {
        return 'heroicon-o-photo';
    }

    public function category(): PageSectionCategory
    {
        return PageSectionCategory::Basic;
    }

    public function defaultData(): array
    {
        return [
            'variant' => 'full_background',
            'heading' => '',
            'subheading' => '',
            'description' => '',
            'video_poster' => '',
            'video_src' => '',
            'button_text' => '',
            'button_url' => '',
            'background_image' => '',
            'overlay_dark' => true,
            'chips' => [],
        ];
    }

    public function formComponents(): array
    {
        return [
            Select::make('data_json.variant')
                ->label('Вариант')
                ->options([
                    'full_background' => 'Фон на всю ширину',
                    'image_right' => 'Картинка справа',
                    'compact' => 'Компактный',
                ])
                ->default('full_background')
                ->native(true),
            TextInput::make('data_json.heading')
                ->label('Заголовок')
                ->maxLength(255)
                ->columnSpanFull()
                ->extraAttributes(['data-setup-target' => 'pages.home.hero_title']),
            TextInput::make('data_json.subheading')
                ->label('Подзаголовок')
                ->maxLength(500)
                ->columnSpanFull(),
            Textarea::make('data_json.description')
                ->label('Описание (под баннером)')
                ->rows(2)
                ->columnSpanFull(),
            TenantPublicImagePicker::make('data_json.video_poster')
                ->label('Постер видео')
                ->helperText(__('Обложка для фона hero. Пусто — постер из общей темы (bundled).'))
                ->uploadPublicSiteSubdirectory('site/videos')
                ->columnSpanFull(),
            TextInput::make('data_json.video_src')
                ->label('Видео hero (MP4)')
                ->placeholder('site/videos/…')
                ->maxLength(2048)
                ->helperText(__('Заглушка: пусто — на сайте нет кнопки «Смотреть видео». Укажите путь к файлу в вашем хранилище (например site/videos/имя.mp4 после загрузки) или полный https-URL. Общий ролик из шаблона темы не подставляется.'))
                ->columnSpanFull(),
            TextInput::make('data_json.button_text')
                ->label('Текст кнопки')
                ->maxLength(120),
            TextInput::make('data_json.button_url')
                ->label('Ссылка кнопки')
                ->rules([new CmsHrefRule])
                ->maxLength(2048),
            TenantPublicImagePicker::make('data_json.background_image')
                ->label('Фоновое изображение')
                ->themeFallbackPreviewPath('marketing/hero-bg.png')
                ->helperText(__('Пустое поле — на сайте используется фон из темы. Свой файл сохраняется в вашем каталоге (S3); файлы темы удалить нельзя.'))
                ->columnSpanFull(),
            Toggle::make('data_json.overlay_dark')
                ->label('Затемнение фона')
                ->default(true),
            TeleportedEditorRepeater::make('data_json.chips')
                ->label('Короткие преимущества (чипы)')
                ->schema([
                    TextInput::make('text')->label('Текст')->required()->maxLength(120),
                ])
                ->defaultItems(0)
                ->addActionLabel('Добавить чип')
                ->columnSpanFull(),
        ];
    }

    public function viewLogicalName(): string
    {
        return 'sections.hero';
    }

    public function previewSummary(array $data): string
    {
        $h = $this->stringPreview($data, 'heading', 60);
        $btn = $this->stringPreview($data, 'button_text', 40);
        $parts = array_filter([$h, $btn ? 'Кнопка: '.$btn : '']);

        return $parts !== [] ? implode(' · ', $parts) : 'Пустой hero';
    }

    public function adminSummary(PageSection $section): SectionAdminSummary
    {
        $data = is_array($section->data_json) ? $section->data_json : [];
        $label = $this->label();
        $listTitle = trim((string) ($section->title ?? ''));
        $displayTitle = $listTitle !== '' ? $listTitle : $label;
        $variant = (string) ($data['variant'] ?? 'full_background');
        $variantLabel = match ($variant) {
            'image_right' => 'Вариант: картинка справа',
            'compact' => 'Вариант: компактный',
            default => 'Вариант: фон на всю ширину',
        };
        $h = trim((string) ($data['heading'] ?? ''));
        $sub = trim((string) ($data['subheading'] ?? ''));
        $btn = trim((string) ($data['button_text'] ?? ''));
        $key = trim((string) ($section->section_key ?? ''));
        $displaySubtitle = $key !== '' ? $key.' · '.$label : $label;
        $isEmpty = $h === '' && $sub === '' && $btn === '';
        $warning = $isEmpty ? 'Баннер без заголовка и призыва к действию' : null;
        $bodyLines = [];
        if ($sub !== '') {
            $bodyLines[] = $sub;
        }
        if ($btn !== '') {
            $bodyLines[] = 'Кнопка: '.$btn;
        }
        $desc = trim((string) ($data['description'] ?? ''));
        if ($desc !== '' && count($bodyLines) < 2) {
            $bodyLines[] = strlen($desc) > 140 ? substr($desc, 0, 137).'…' : $desc;
        }
        $videoSrc = trim((string) ($data['video_src'] ?? ''));
        $videoPoster = trim((string) ($data['video_poster'] ?? ''));
        $bg = trim((string) ($data['background_image'] ?? ''));
        $chips = $data['chips'] ?? [];
        $chipCount = is_array($chips) ? count(array_filter($chips, fn ($c): bool => is_array($c) && trim((string) ($c['text'] ?? '')) !== '')) : 0;
        $channels = [
            ['icon' => 'heroicon-o-play-circle', 'label' => 'Видео', 'on' => $videoSrc !== '' || $videoPoster !== ''],
            ['icon' => 'heroicon-o-photo', 'label' => 'Фон', 'on' => $bg !== ''],
            ['icon' => 'heroicon-o-cursor-arrow-rays', 'label' => 'Кнопка', 'on' => $btn !== ''],
            ['icon' => 'heroicon-o-sparkles', 'label' => 'Чипы', 'on' => $chipCount > 0],
        ];
        $badges = [$variantLabel];
        if ($chipCount > 0) {
            $badges[] = 'Чипов: '.$chipCount;
        }

        return new SectionAdminSummary(
            displayTitle: $displayTitle,
            displaySubtitle: $displaySubtitle,
            summaryLines: $bodyLines !== [] ? $bodyLines : ($isEmpty ? ['Пустой баннер'] : []),
            badges: $badges,
            meta: ['variant' => $variant],
            isEmpty: $isEmpty,
            warning: $warning,
            primaryHeadline: $h !== '' ? $h : null,
            channels: $channels,
        );
    }
}
