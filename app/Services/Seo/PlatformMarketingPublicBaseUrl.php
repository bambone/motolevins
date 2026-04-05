<?php

namespace App\Services\Seo;

/**
 * Canonical HTTPS base URL for the platform marketing site (central domains), without trailing slash.
 */
final class PlatformMarketingPublicBaseUrl
{
    public function resolve(): string
    {
        $fromConfig = trim((string) config('platform_marketing.organization.url', ''));
        if ($fromConfig !== '') {
            return rtrim($fromConfig, '/');
        }

        $central = config('tenancy.central_domains', []);
        if (is_array($central)) {
            foreach ($central as $domain) {
                $host = trim((string) $domain);
                if ($host !== '') {
                    return 'https://'.$host;
                }
            }
        }

        return rtrim((string) config('app.url'), '/');
    }
}
