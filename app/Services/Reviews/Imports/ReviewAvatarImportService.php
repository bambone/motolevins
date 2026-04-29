<?php

declare(strict_types=1);

namespace App\Services\Reviews\Imports;

use App\Support\Storage\TenantStorageDisks;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final class ReviewAvatarImportService
{
    private const ALLOWED_MIME = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function downloadToTenantPublic(string $url, int $tenantId, string $provider): ?string
    {
        if (! $this->isSafeHttpUrl($url)) {
            return null;
        }

        try {
            $resp = Http::timeout(8)->withHeaders(['User-Agent' => 'RentBaseReviews/1.0'])->get($url);
            if (! $resp->ok()) {
                return null;
            }
            $body = $resp->body();
            if (strlen($body) > 2_000_000) {
                return null;
            }
            $mime = $resp->header('Content-Type');
            $mime = $mime ? explode(';', $mime)[0] : '';
            $mime = trim(strtolower($mime));
            if (! isset(self::ALLOWED_MIME[$mime])) {
                return null;
            }
            $hash = hash('sha256', $body);
            $ext = self::ALLOWED_MIME[$mime];
            $relative = 'reviews/avatars/'.$provider.'/'.$hash.'.'.$ext;
            $disk = TenantStorageDisks::publicDiskName();
            Storage::disk($disk)->put('tenants/'.$tenantId.'/public/'.$relative, $body, ['visibility' => 'public']);

            return $relative;
        } catch (Throwable $e) {
            Log::notice('review_avatar_import_failed', [
                'tenant_id' => $tenantId,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function isSafeHttpUrl(string $url): bool
    {
        if (! preg_match('#^https://#i', $url)) {
            return false;
        }
        $host = parse_url($url, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return false;
        }
        if (Str::endsWith($host, '.localhost')) {
            return false;
        }
        $ip = gethostbyname($host);
        if ($ip === $host) {
            // unresolved — allow public hostname
            return ! filter_var($host, FILTER_VALIDATE_IP);
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }

        return true;
    }
}
