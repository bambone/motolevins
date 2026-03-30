<?php

namespace App\Services\Seo;

use App\Models\Tenant;
use App\Models\TenantSeoFile;

/**
 * Same resolution path for HTTP responses and admin preview (no drift).
 */
final class TenantSeoPublicContentService
{
    public function __construct(
        private TenantSeoSnapshotReader $snapshots,
        private RobotsTxtGenerator $robots,
        private SitemapGenerator $sitemap,
    ) {}

    public function robotsBody(Tenant $tenant): string
    {
        $snap = $this->snapshots->readValid($tenant->id, TenantSeoFile::TYPE_ROBOTS_TXT);
        if ($snap !== null) {
            return $snap;
        }

        return $this->robots->generate($tenant);
    }

    /**
     * Precondition: sitemap feature enabled for tenant (caller checks TenantSetting).
     */
    public function sitemapBodyForEnabledTenant(Tenant $tenant): string
    {
        $snap = $this->snapshots->readValid($tenant->id, TenantSeoFile::TYPE_SITEMAP_XML);
        if ($snap !== null) {
            return $snap;
        }

        return $this->sitemap->generateXml($tenant);
    }
}
