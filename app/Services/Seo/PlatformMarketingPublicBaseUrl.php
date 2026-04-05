<?php

namespace App\Services\Seo;

/**
 * Canonical base URL for the platform marketing site (central domains), without trailing slash.
 * Scheme follows APP_URL when building from host list so local http://*.test matches the dev server.
 */
final class PlatformMarketingPublicBaseUrl
{
    public function resolve(): string
    {
        $fromConfig = trim((string) config('platform_marketing.organization.url', ''));
        if ($fromConfig !== '') {
            return rtrim($fromConfig, '/');
        }

        $scheme = $this->schemeFromAppUrl();

        $central = config('tenancy.central_domains', []);
        if (is_array($central)) {
            foreach ($central as $domain) {
                $host = trim((string) $domain);
                if ($host !== '') {
                    return $scheme.'://'.$host;
                }
            }
        }

        return rtrim((string) config('app.url'), '/');
    }

    private function schemeFromAppUrl(): string
    {
        $parsed = parse_url((string) config('app.url')) ?: [];
        $scheme = $parsed['scheme'] ?? 'https';

        return in_array($scheme, ['http', 'https'], true) ? $scheme : 'https';
    }
}