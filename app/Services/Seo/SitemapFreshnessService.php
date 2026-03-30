<?php

namespace App\Services\Seo;

use App\Models\Tenant;
use App\Models\TenantSeoFile;
use App\Models\TenantSetting;

final class SitemapFreshnessService
{
    public const STATUS_MISSING = 'missing';

    public const STATUS_FRESH = 'fresh';

    public const STATUS_STALE_CONTENT = 'stale_due_to_content_changes';

    public const STATUS_STALE_AGE = 'stale_due_to_age';

    public const STATUS_ERROR = 'error';

    public function __construct(
        private TenantSeoSnapshotReader $snapshots,
        private PublicContentLastUpdatedService $contentLastUpdated,
    ) {}

    public function resolveStatus(Tenant $tenant): string
    {
        $row = TenantSeoFile::query()
            ->where('tenant_id', $tenant->id)
            ->where('type', TenantSeoFile::TYPE_SITEMAP_XML)
            ->first();

        $contentAt = $this->contentLastUpdated->lastUpdatedAt($tenant);

        try {
            $snapshot = $this->snapshots->readValid($tenant->id, TenantSeoFile::TYPE_SITEMAP_XML);
        } catch (\Throwable) {
            return self::STATUS_ERROR;
        }

        if ($snapshot === null) {
            return self::STATUS_MISSING;
        }

        $generatedAt = $row?->generated_at;
        if ($generatedAt === null) {
            return self::STATUS_MISSING;
        }

        $ttlDays = (int) TenantSetting::getForTenant(
            $tenant->id,
            'seo.sitemap_stale_after_days',
            (int) config('seo.sitemap_stale_after_days_default', 7)
        );
        if ($ttlDays < 1) {
            $ttlDays = 7;
        }

        if ($contentAt !== null && $contentAt->greaterThan($generatedAt)) {
            return self::STATUS_STALE_CONTENT;
        }

        if ($generatedAt->copy()->addDays($ttlDays)->isPast()) {
            return self::STATUS_STALE_AGE;
        }

        return self::STATUS_FRESH;
    }

    public function syncSitemapRowMetadata(Tenant $tenant): void
    {
        $row = TenantSeoFile::query()
            ->where('tenant_id', $tenant->id)
            ->where('type', TenantSeoFile::TYPE_SITEMAP_XML)
            ->first();

        if ($row === null) {
            return;
        }

        $row->update([
            'freshness_status' => $this->resolveStatus($tenant),
            'last_public_content_change_at' => $this->contentLastUpdated->lastUpdatedAt($tenant),
            'last_checked_at' => now(),
        ]);
    }
}
