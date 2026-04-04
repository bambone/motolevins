<?php

namespace App\Filament\Tenant\Support;

use App\Support\Storage\TenantStorage;
use App\Support\Storage\TenantStorageArea;
use App\Support\Storage\TenantStorageDisks;
use Filament\Forms\Components\RichEditor;

/**
 * Единые настройки RichEditor для контента страниц тенанта: TipTap toolbar, таблицы, вложения.
 *
 * Файлы вложений уходят в публичную зону хранилища тенанта (см. {@see TenantStorage} / настройки диска).
 */
final class TenantPageRichEditor
{
    /**
     * Группы кнопок панели (вставка таблицы, изображения, отмена — как в дефолте Filament).
     *
     * @return list<list<string>>
     */
    public static function toolbarButtonGroups(): array
    {
        return [
            ['bold', 'italic', 'underline', 'strike', 'link'],
            ['h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd'],
            ['blockquote', 'codeBlock', 'bulletList', 'orderedList'],
            ['table', 'attachFiles', 'horizontalRule'],
            ['undo', 'redo'],
        ];
    }

    public static function enhance(RichEditor $editor, bool $withAttachmentHelp = true): RichEditor
    {
        $editor = $editor
            ->toolbarButtons(self::toolbarButtonGroups())
            ->resizableImages()
            ->fileAttachmentsDisk(fn (): string => TenantStorageDisks::publicDiskName())
            ->fileAttachmentsDirectory(function (): string {
                $tenant = currentTenant();
                if ($tenant === null) {
                    return TenantStorage::forTrusted(0)->publicPathInArea(TenantStorageArea::PublicSite, 'page-content');
                }

                return TenantStorage::forTrusted((int) $tenant->id)
                    ->publicPathInArea(TenantStorageArea::PublicSite, 'page-content');
            })
            ->fileAttachmentsVisibility('public');

        if ($withAttachmentHelp) {
            $editor = $editor->helperText(
                'Картинки и вложения сохраняются в файлах вашего сайта. Поставьте курсор в нужное место → «Прикрепить файл». '.
                'Для изображения можно указать подпись (alt) и размер рамки.'
            );
        }

        return $editor;
    }
}
