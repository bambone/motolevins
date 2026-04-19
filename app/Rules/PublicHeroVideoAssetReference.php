<?php

namespace App\Rules;

use App\Support\Storage\TenantPublicAssetResolver;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Как {@see PublicAssetReference}, плюс короткие legacy-пути hero-видео, которые на сайте
 * обрабатывает {@see TenantPublicAssetResolver::resolveHeroVideo()}.
 */
final class PublicHeroVideoAssetReference implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }
        if (! is_string($value)) {
            $fail(__('Некорректное значение.'));

            return;
        }
        $v = trim($value);
        if ($v === '') {
            return;
        }
        if (preg_match('#^https?://#i', $v) === 1) {
            if (filter_var($v, FILTER_VALIDATE_URL) === false) {
                $fail(__('Укажите корректный URL.'));
            }

            return;
        }
        if (str_contains($v, '..')) {
            $fail(__('Укажите URL или ключ файла в хранилище (tenants/…/public/…).'));

            return;
        }
        if (preg_match('#^tenants/\d+/public/.+#', $v) === 1) {
            return;
        }
        if (preg_match('#^(?:site|storage)/#', $v) === 1) {
            return;
        }
        if (preg_match('#^images/(?:motolevins|motolevin)/videos/.+#i', $v) === 1) {
            return;
        }
        if (preg_match('#^videos/.+#i', $v) === 1) {
            return;
        }
        if (preg_match('#^themes/.+/videos/.+#i', $v) === 1) {
            return;
        }
        if (preg_match('#^[^/\\\\]+\.(?:mp4|webm)$#i', $v) === 1) {
            return;
        }

        $fail(__('Укажите URL или путь к MP4/WebM (например site/videos/…).'));
    }
}
