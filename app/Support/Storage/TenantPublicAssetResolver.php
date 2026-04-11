<?php

namespace App\Support\Storage;

use App\Models\Tenant;
use Illuminate\Support\Facades\Route;
use Throwable;

/**
 * Разрешение значений из JSON секций / настроек в публичный URL для img и CSS background.
 * Поддерживает legacy http(s) URL и object keys вида {@code tenants/{id}/public/...} на tenant public disk.
 *
 * В HTTP-запросе: если задан {@see config('tenant_storage.public_cdn_base_url')} и публичный диск не локальный Flysystem,
 * отдаётся прямой CDN/R2 URL (как {@see TenantStorage::publicUrl()}), иначе same-origin
 * {@code /storage/tenants/{id}/public/...} через {@see TenantPublicStorageFileController} (локальная разработка и legacy).
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
            return self::rewriteTenantPublicStorageUrlIfCdnConfigured($v, $tenantId) ?? $v;
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
     * Старые записи в JSON/настройках: полный URL на {@code /storage/tenants/{id}/public/...} на домене сайта.
     * При включённом CDN и облачном диске переписываем на прямой R2/CDN URL, иначе каждый запрос идёт в Laravel (302) и только потом на объект.
     *
     * @return non-empty-string|null
     */
    private static function rewriteTenantPublicStorageUrlIfCdnConfigured(string $url, int $tenantId): ?string
    {
        $cdn = rtrim((string) config('tenant_storage.public_cdn_base_url', ''), '/');
        if ($cdn === '') {
            return null;
        }
        if (TenantStorageDisks::usesLocalFlyAdapter(TenantStorageDisks::publicDisk())) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            return null;
        }

        if (preg_match('#/storage/tenants/(\d+)/public/(.+)$#', $path, $m) !== 1) {
            return null;
        }
        if ((int) $m[1] !== $tenantId) {
            return null;
        }

        $rel = rawurldecode($m[2]);
        $rel = str_replace('\\', '/', $rel);
        $rel = ltrim($rel, '/');
        if ($rel === '' || str_contains($rel, '..')) {
            return null;
        }

        if (str_starts_with($rel, 'expert_auto/')) {
            $rel = 'site/'.$rel;
        }

        try {
            $direct = TenantStorage::forTrusted($tenantId)->publicUrl($rel);
        } catch (Throwable) {
            return null;
        }
        $direct = trim($direct);
        if ($direct === '') {
            return null;
        }

        $query = parse_url($url, PHP_URL_QUERY);
        if (is_string($query) && $query !== '') {
            $direct .= (str_contains($direct, '?') ? '&' : '?').$query;
        }
        $fragment = parse_url($url, PHP_URL_FRAGMENT);
        if (is_string($fragment) && $fragment !== '') {
            $direct .= '#'.$fragment;
        }

        return $direct;
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

        $cdn = rtrim((string) config('tenant_storage.public_cdn_base_url', ''), '/');
        $useDirectCdnUrl = $cdn !== '' && ! TenantStorageDisks::usesLocalFlyAdapter(TenantStorageDisks::publicDisk());

        if ($useDirectCdnUrl) {
            return TenantStorage::forTrusted($tenantId)->publicUrl($pathUnderPublicSegment);
        }

        // PHPUnit may report runningInConsole() during HTTP tests; наличие route совпадает с реальным web-запросом.
        $hasHttpRoute = Route::has('tenant.public.storage')
            && (! app()->runningInConsole() || request()->route() !== null);

        if ($hasHttpRoute) {
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
            return self::rewriteTenantPublicStorageUrlIfCdnConfigured($v, (int) $tenant->id) ?? $v;
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
