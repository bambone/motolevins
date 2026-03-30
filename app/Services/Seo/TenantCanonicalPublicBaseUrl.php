<?php

namespace App\Services\Seo;

use App\Models\Tenant;
use App\Models\TenantSetting;

/**
 * Canonical public site base URL for SEO (sitemap loc, robots Sitemap: line).
 * Does not use the current request host so admin/platform hosts never leak into URLs.
 */
final class TenantCanonicalPublicBaseUrl
{
    public function resolve(Tenant $tenant): string
    {
        $stored = trim((string) TenantSetting::getForTenant($tenant->id, 'general.domain', ''));
        if ($stored !== '' && filter_var($stored, FILTER_VALIDATE_URL)) {
            return rtrim($stored, '/');
        }

        $primary = $tenant->primaryDomain();
        if ($primary !== null && filled($primary->host)) {
            return 'https://'.strtolower(trim((string) $primary->host));
        }

        return rtrim((string) config('app.url'), '/');
    }
}
