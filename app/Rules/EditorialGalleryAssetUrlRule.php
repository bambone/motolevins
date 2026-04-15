<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Валидация path/URL для Expert: Галерея — изображение, постер или видеофайл (не HTML-страницы и не embed-страницы).
 */
final class EditorialGalleryAssetUrlRule implements ValidationRule
{
    public const KIND_IMAGE = 'image';

    public const KIND_POSTER = 'poster';

    public const KIND_VIDEO_FILE = 'video_file';

    public function __construct(
        private readonly string $kind,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $v = trim((string) $value);
        if ($v === '') {
            return;
        }

        $lower = strtolower($v);
        if (str_contains($v, '<') || str_contains($lower, 'iframe') || str_contains($lower, '<script')) {
            $fail(__('Укажите путь или URL файла, без HTML и iframe.'));

            return;
        }

        if (self::looksLikeVideoHostPage($v)) {
            $fail(__('Это ссылка на страницу видеохостинга. Для VK/YouTube выберите тип «Видео (встраивание)» или укажите прямой файл MP4/WebM.'));

            return;
        }

        if ($this->kind === self::KIND_VIDEO_FILE) {
            if (! self::looksLikeVideoFile($v)) {
                $fail(__('Укажите путь к видеофайлу в хранилище или прямой URL с расширением MP4/WebM/OGV.'));

                return;
            }

            return;
        }

        if (! self::looksLikeImageAsset($v)) {
            $fail(__('Укажите путь к изображению в хранилище (например site/brand/…) или прямой URL файла изображения.'));

            return;
        }
    }

    private static function looksLikeVideoHostPage(string $v): bool
    {
        $lower = strtolower($v);

        return str_contains($lower, 'youtube.com/watch')
            || str_contains($lower, 'youtu.be/')
            || preg_match('~vk\.com/video(-?\d+_\d+)?~', $lower) === 1
            || str_contains($lower, 'vimeo.com/');
    }

    private static function looksLikeVideoFile(string $v): bool
    {
        $path = parse_url($v, PHP_URL_PATH);
        $path = is_string($path) && $path !== '' ? $path : $v;
        if (preg_match('/\.(mp4|webm|ogv)(?:\?.*)?$/i', $path) === 1) {
            return true;
        }

        return preg_match('#^site/.+\.(mp4|webm|ogv)(?:\?|$)#i', $v) === 1
            || preg_match('#^storage/.+\.(mp4|webm|ogv)(?:\?|$)#i', $v) === 1;
    }

    private static function looksLikeImageAsset(string $v): bool
    {
        if (preg_match('#^site/#', $v) === 1 || preg_match('#^storage/#', $v) === 1) {
            return true;
        }
        if (str_starts_with($v, '/') && ! str_starts_with($v, '//')) {
            return true;
        }
        $path = parse_url($v, PHP_URL_PATH);
        $path = is_string($path) ? $path : $v;
        if (preg_match('/\.(jpe?g|png|gif|webp|avif|svg)(?:\?.*)?$/i', $path) === 1) {
            return true;
        }

        return false;
    }
}
