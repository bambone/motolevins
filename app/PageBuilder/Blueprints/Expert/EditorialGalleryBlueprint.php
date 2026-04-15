<?php

namespace App\PageBuilder\Blueprints\Expert;

use App\Filament\Tenant\PageBuilder\TeleportedEditorRepeater;
use App\PageBuilder\PageSectionCategory;
use App\Rules\EditorialGalleryAssetUrlRule;
use App\Rules\EditorialGalleryCaptionRule;
use App\Rules\EditorialGalleryMaterialSourceUrlRule;
use App\Tenant\Expert\VideoEmbedUrlNormalizer;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

final class EditorialGalleryBlueprint extends ExpertSectionBlueprint
{
    public function id(): string
    {
        return 'editorial_gallery';
    }

    public function label(): string
    {
        return 'Expert: Галерея';
    }

    public function description(): string
    {
        return 'Редакторская подборка кадров и видео. Превью и постеры предпочтительно загружать в хранилище сайта, а не hotlink. Внешние статьи — поле «Ссылка на материал».';
    }

    public function icon(): string
    {
        return 'heroicon-o-photo';
    }

    public function category(): PageSectionCategory
    {
        return PageSectionCategory::Content;
    }

    public function defaultData(): array
    {
        return [
            'section_heading' => '',
            'section_lead' => '',
            'items' => [],
        ];
    }

    public function formComponents(): array
    {
        return [
            TextInput::make('data_json.section_heading')->label('Заголовок')->maxLength(255)->columnSpanFull(),
            Textarea::make('data_json.section_lead')->label('Лид под заголовком')->rows(2)->columnSpanFull(),
            TeleportedEditorRepeater::make('data_json.items')
                ->label('Кадры и видео')
                ->defaultItems(0)
                ->addActionLabel('Добавить материал')
                ->addAction(function (Action $action): Action {
                    return TeleportedEditorRepeater::withFullLivewireRenderAfter(
                        $action->action(function (Repeater $component): void {
                            $newUuid = $component->generateUuid();
                            $items = $component->getRawState();
                            $seed = [
                                'media_kind' => 'image',
                                'source_new_tab' => true,
                            ];
                            if ($newUuid) {
                                $items[$newUuid] = $seed;
                            } else {
                                $items[] = $seed;
                            }
                            $component->rawState($items);
                            $component->getChildSchema($newUuid ?? array_key_last($items))->fill();
                            $component->collapsed(false, shouldMakeComponentCollapsible: false);
                            $component->callAfterStateUpdated();
                        })
                    );
                })
                ->schema([
                    Select::make('media_kind')
                        ->label('Тип')
                        ->options([
                            'image' => 'Фото',
                            'video' => 'Видео (файл MP4/WebM)',
                            'video_embed' => 'Видео (встраивание VK/YouTube)',
                        ])
                        ->default('image')
                        ->live(),
                    TextInput::make('image_url')
                        ->label('Изображение (путь или URL)')
                        ->maxLength(2048)
                        ->visible(fn ($get): bool => ($get('media_kind') ?? '') === 'image')
                        ->required(fn ($get): bool => ($get('media_kind') ?? '') === 'image')
                        ->helperText('Путь в хранилище сайта (например site/brand/…) или прямой URL файла изображения. Не вставляйте ссылку на HTML-страницу статьи.')
                        ->rules([
                            fn ($get): array => ($get('media_kind') ?? '') === 'image'
                                ? [new EditorialGalleryAssetUrlRule(EditorialGalleryAssetUrlRule::KIND_IMAGE)]
                                : [],
                        ]),
                    TextInput::make('video_url')
                        ->label('Видеофайл (путь или URL)')
                        ->maxLength(2048)
                        ->visible(fn ($get): bool => ($get('media_kind') ?? '') === 'video')
                        ->required(fn ($get): bool => ($get('media_kind') ?? '') === 'video')
                        ->helperText('Путь к файлу в хранилище или прямой URL видеофайла (MP4/WebM). Ссылка на страницу VK/YouTube здесь не работает — выберите тип «Видео (встраивание)».')
                        ->rules([
                            fn ($get): array => ($get('media_kind') ?? '') === 'video'
                                ? [new EditorialGalleryAssetUrlRule(EditorialGalleryAssetUrlRule::KIND_VIDEO_FILE)]
                                : [],
                        ]),
                    TextInput::make('poster_url')
                        ->label('Постер видео (путь или URL)')
                        ->maxLength(2048)
                        ->visible(fn ($get): bool => in_array($get('media_kind'), ['video', 'video_embed'], true))
                        ->helperText('Только изображение-постер. Не HTML, не iframe, не страница новости. Для ровной сетки превью на сайте постер лучше указать.')
                        ->rules([
                            fn ($get): array => in_array($get('media_kind'), ['video', 'video_embed'], true)
                                ? [new EditorialGalleryAssetUrlRule(EditorialGalleryAssetUrlRule::KIND_POSTER)]
                                : [],
                        ]),
                    Select::make('embed_provider')
                        ->label('Площадка встраивания')
                        ->options([
                            'youtube' => 'YouTube',
                            'vk' => 'ВКонтакте',
                        ])
                        ->visible(fn ($get): bool => ($get('media_kind') ?? '') === 'video_embed')
                        ->required(fn ($get): bool => ($get('media_kind') ?? '') === 'video_embed')
                        ->live()
                        ->rules([
                            fn ($get): array => ($get('media_kind') ?? '') === 'video_embed'
                                ? ['required', 'in:youtube,vk']
                                : [],
                        ]),
                    TextInput::make('embed_share_url')
                        ->label('Ссылка на ролик (страница share)')
                        ->maxLength(2048)
                        ->visible(fn ($get): bool => ($get('media_kind') ?? '') === 'video_embed')
                        ->required(fn ($get): bool => ($get('media_kind') ?? '') === 'video_embed')
                        ->helperText(function ($get): string {
                            return match ($get('embed_provider')) {
                                'vk' => 'Укажите ссылку на ролик ВКонтакте: vk.com/video-…_… или страницу video_ext.php на vk.com. Не вставляйте HTML и не код iframe.',
                                'youtube' => 'Укажите ссылку на ролик YouTube: youtube.com/watch?v=…, youtu.be/…, а также /shorts/…, /embed/… или /live/…. Не вставляйте HTML и не код iframe.',
                                default => 'Сначала выберите площадку (YouTube или ВКонтакте) — подсказка обновится.',
                            };
                        })
                        ->rules([
                            fn ($get): array => ($get('media_kind') ?? '') === 'video_embed'
                                ? [
                                    function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                                        $v = trim((string) $value);
                                        if ($v === '') {
                                            return;
                                        }
                                        $p = (string) ($get('embed_provider') ?? '');
                                        if (VideoEmbedUrlNormalizer::toIframeSrc($p, $v) === null) {
                                            $fail(__('Не удалось разобрать ссылку для выбранного провайдера.'));
                                        }
                                    },
                                ]
                                : [],
                        ]),
                    TextInput::make('caption')
                        ->label('Подпись')
                        ->maxLength(255)
                        ->helperText('Обычный текст, без HTML и без копипаста из кода страницы.')
                        ->rules([new EditorialGalleryCaptionRule]),
                    TextInput::make('source_url')
                        ->label('Ссылка на материал (источник)')
                        ->maxLength(2048)
                        ->live(onBlur: true)
                        ->helperText('Только полный URL с https:// или http://. Относительные пути, «протокол-relative» (//…), якоря (#…), mailto и tel не используются.')
                        ->rules([new EditorialGalleryMaterialSourceUrlRule]),
                    TextInput::make('source_label')
                        ->label('Текст ссылки на источник')
                        ->maxLength(120)
                        ->placeholder('Читать материал')
                        ->visible(fn ($get): bool => trim((string) ($get('source_url') ?? '')) !== '')
                        ->helperText('Например: «Открыть источник», «Читать на сайте ФПА». Пусто — подпись по умолчанию.'),
                    Toggle::make('source_new_tab')
                        ->label('Открывать источник в новой вкладке')
                        ->default(true)
                        ->visible(fn ($get): bool => trim((string) ($get('source_url') ?? '')) !== '')
                        ->inline(false),
                ])
                ->columnSpanFull(),
        ];
    }

    public function viewLogicalName(): string
    {
        return 'sections.editorial_gallery';
    }

    public function previewSummary(array $data): string
    {
        $items = is_array($data['items'] ?? null) ? $data['items'] : [];
        $nImage = 0;
        $nVideo = 0;
        $nEmbed = 0;
        foreach ($items as $row) {
            if (! is_array($row)) {
                continue;
            }
            $k = trim((string) ($row['media_kind'] ?? ''));
            if ($k === '') {
                $k = trim((string) ($row['video_url'] ?? '')) !== '' ? 'video' : 'image';
            }
            match ($k) {
                'video_embed' => $nEmbed++,
                'video' => $nVideo++,
                default => $nImage++,
            };
        }
        $n = $nImage + $nVideo + $nEmbed;
        if ($n === 0) {
            return 'Нет материалов';
        }
        $word = match (true) {
            $n % 10 === 1 && $n % 100 !== 11 => 'материал',
            in_array($n % 10, [2, 3, 4], true) && ! in_array($n % 100, [12, 13, 14], true) => 'материала',
            default => 'материалов',
        };
        $parts = [];
        if ($nImage > 0) {
            $parts[] = $nImage.' фото';
        }
        if ($nVideo > 0) {
            $parts[] = $nVideo.' видео';
        }
        if ($nEmbed > 0) {
            $parts[] = self::embeddedVideosLabel($nEmbed);
        }

        return $n.' '.$word.': '.implode(', ', $parts);
    }

    private static function embeddedVideosLabel(int $n): string
    {
        $phrase = ($n % 10 === 1 && $n % 100 !== 11)
            ? 'встроенное видео'
            : 'встроенных видео';

        return $n.' '.$phrase;
    }
}
