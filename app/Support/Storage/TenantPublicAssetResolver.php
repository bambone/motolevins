<?php

namespace App\Support\Storage;

use App\Models\Tenant;
use Illuminate\Support\Facades\Route;
use Throwable;

/**
 * Разрешение значений из JSON секций / настроек в публичный URL для img и CSS background.
 * Поддерживает legacy http(s) URL и object keys вида {@code tenants/{id}/public/...} на tenant public disk.
 *
 * В HTTP-запросе (публичный сайт, предпросмотр в админке) для ключей и относительных путей отдаётся
 * same-origin URL {@code /storage/tenants/{id}/public/...}, чтобы {@see TenantPublicStorageFileController}
 * выставил корректный {@code Content-Type} и не срабатывал {@code ERR_BLOCKED_BY_ORB} на прямых ссылках R2/CDN.
 */
final class TenantPublicAssetResolver
{
    /**
     * @return non-empty-string|null
     */
    public static function resolve(?string $value, int $tenantId): ?string
    {
        $v = trim((string) $value);
        if ($v === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $v) === 1) {
            return $v;
        }

        if (preg_match('#^tenants/(\d+)/public/(.+)$#', $v, $m) === 1) {
            $id = (int) $m[1];
            if ($id !== $tenantId) {
                return null;
            }
            $relativeUnderPublic = $m[2];

            return self::safePublicUrl($tenantId, $relativeUnderPublic);
        }

        if (str_starts_with($v, 'tenants/')) {
            return null;
        }

        return self::safePublicUrl($tenantId, ltrim($v, '/'));
    }

    /**
     * @return non-empty-string|null
     */
    private static function safePublicUrl(int $tenantId, string $pathUnderPublicSegment): ?string
    {
        try {
            $url = self::publicAssetUrlForRequestContext($tenantId, $pathUnderPublicSegment);
            $url = trim($url);

            return $url !== '' ? $url : null;
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * @throws Throwable
     */
    private static function publicAssetUrlForRequestContext(int $tenantId, string $pathUnderPublicSegment): string
    {
        $pathUnderPublicSegment = ltrim(str_replace('\\', '/', $pathUnderPublicSegment), '/');
        if ($pathUnderPublicSegment === '' || str_contains($pathUnderPublicSegment, '..')) {
            return '';
        }

        // Legacy: файлы в зоне PublicSite лежат под site/…; старые ключи без префикса давали 404.
        if (str_starts_with($pathUnderPublicSegment, 'expert_auto/')) {
            $pathUnderPublicSegment = 'site/'.$pathUnderPublicSegment;
        }

        if (! app()->runningInConsole() && Route::has('tenant.public.storage')) {
            return route('tenant.public.storage', [
                'tenantId' => $tenantId,
                'path' => $pathUnderPublicSegment,
            ], absolute: true);
        }

        return TenantStorage::forTrusted($tenantId)->publicUrl($pathUnderPublicSegment);
    }

    public static function resolveForCurrentTenant(?string $value): ?string
    {
        $t = \currentTenant();
        if ($t === null) {
            return null;
        }

        return self::resolve($value, (int) $t->id);
    }

    /**
     * @return non-empty-string|null
     */
    public static function resolveForTenantModel(?string $value, ?Tenant $tenant): ?string
    {
        if ($tenant === null) {
            return null;
        }

        return self::resolve($value, (int) $tenant->id);
    }

    /**
     * URL hero-видео только из пространства тенанта (или внешний https). Без fallback на bundled-тему _system.
     *
     * @return non-empty-string|null
     */
    public static function resolveHeroVideo(?string $value, Tenant $tenant): ?string
    {
        $v = trim((string) $value);
        if ($v === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $v) === 1) {
            return $v;
        }

        $ts = TenantStorage::forTrusted($tenant);
        $themeKey = $tenant->themeKey();

        $tenantId = (int) $tenant->id;
        $urlIfExists = function (string $relativeUnderPublic) use ($ts, $tenantId): ?string {
            $relativeUnderPublic = ltrim(str_replace('\\', '/', $relativeUnderPublic), '/');
            if ($relativeUnderPublic === '') {
                return null;
            }
            if (! $ts->existsPublic($relativeUnderPublic)) {
                return null;
            }

            try {
                $url = self::publicAssetUrlForRequestContext($tenantId, $relativeUnderPublic);
            } catch (Throwable) {
                return null;
            }

            return $url !== '' ? $url : null;
        };

        if (preg_match('#^tenants/'.$tenantId.'/public/(.+)$#', $v, $m)) {
            return $urlIfExists($m[1]);
        }

        if (preg_match('#^tenants/\d+/public/#', $v)) {
            return null;
        }

        if (preg_match('#^images/(?:motolevins|motolevin)/videos/([^/]+\.(?:mp4|webm))$#i', $v, $m)) {
            return $urlIfExists('site/videos/'.$m[1])
                ?? $urlIfExists('themes/'.$themeKey.'/videos/'.$m[1]);
        }

        if (preg_match('#^videos/([^/]+\.(?:mp4|webm))$#i', $v, $m)) {
            return $urlIfExists('site/videos/'.$m[1]);
        }

        if (preg_match('#^[^/\\\\]+\.(?:mp4|webm)$#i', $v)) {
            return $urlIfExists('site/videos/'.$v);
        }

        if (preg_match('#^themes/[^/]+/videos/([^/]+\.(?:mp4|webm))$#i', $v, $m)) {
            return $urlIfExists('themes/'.$themeKey.'/videos/'.$m[1])
                ?? $urlIfExists('site/videos/'.$m[1]);
        }

        $rel = ltrim(str_replace('\\', '/', $v), '/');

        return $urlIfExists($rel);
    }
}
