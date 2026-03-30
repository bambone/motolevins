<?php

namespace App\Services\Seo;

use App\Models\Tenant;
use App\Models\TenantSeoFile;
use App\Models\TenantSeoFileGeneration;
use App\Services\Seo\Exceptions\RobotsSnapshotOverwriteNotConfirmedException;
use Carbon\CarbonInterface;
use InvalidArgumentException;
use Throwable;

final class TenantSeoFilePublisher
{
    public function __construct(
        private SeoFileStorage $storage,
        private TenantSeoSnapshotReader $reader,
        private RobotsTxtGenerator $robots,
        private SitemapGenerator $sitemap,
        private TenantCanonicalPublicBaseUrl $canonical,
        private PublicContentLastUpdatedService $contentLastUpdated,
        private SitemapFreshnessService $freshness,
    ) {}

    /**
     * @throws RobotsSnapshotOverwriteNotConfirmedException
     */
    public function publishRobots(
        Tenant $tenant,
        ?int $userId,
        string $triggerSource,
        bool $overwriteConfirmed,
        bool $createBackup,
    ): void {
        $hadSnapshot = $this->reader->readValid($tenant->id, TenantSeoFile::TYPE_ROBOTS_TXT) !== null;
        $startedAt = now();
        if ($hadSnapshot && ! $overwriteConfirmed) {
            $this->recordGeneration(
                $tenant,
                TenantSeoFile::TYPE_ROBOTS_TXT,
                $triggerSource,
                $userId,
                false,
                TenantSeoFileGeneration::STATUS_FAILED,
                'Overwrite not confirmed',
                $startedAt,
                false,
                null
            );
            throw new RobotsSnapshotOverwriteNotConfirmedException(
                'Подтвердите перезапись существующего robots.txt.'
            );
        }
        $backupPath = null;
        $backupCreated = false;

        try {
            if ($hadSnapshot && $createBackup) {
                $current = $this->reader->readValid($tenant->id, TenantSeoFile::TYPE_ROBOTS_TXT);
                if ($current !== null) {
                    $backup = $this->storage->createBackup($tenant->id, TenantSeoFile::TYPE_ROBOTS_TXT, $current);
                    $backupPath = $backup['path'];
                    $backupCreated = true;
                }
            }

            $content = $this->robots->generate($tenant);
            $content = $this->robots->validateForSnapshot($content);
            $this->storage->writeSnapshot($tenant->id, TenantSeoFile::TYPE_ROBOTS_TXT, $content);

            $this->upsertFileState(
                $tenant,
                TenantSeoFile::TYPE_ROBOTS_TXT,
                $content,
                $userId,
                $triggerSource,
                $backupPath
            );

            $this->recordGeneration(
                $tenant,
                TenantSeoFile::TYPE_ROBOTS_TXT,
                $triggerSource,
                $userId,
                $overwriteConfirmed,
                TenantSeoFileGeneration::STATUS_SUCCESS,
                null,
                $startedAt,
                $backupCreated,
                $backupPath
            );
        } catch (Throwable $e) {
            $this->recordGeneration(
                $tenant,
                TenantSeoFile::TYPE_ROBOTS_TXT,
                $triggerSource,
                $userId,
                $overwriteConfirmed,
                TenantSeoFileGeneration::STATUS_FAILED,
                $e->getMessage(),
                $startedAt,
                $backupCreated,
                $backupPath
            );
            throw $e;
        }
    }

    public function publishSitemap(
        Tenant $tenant,
        ?int $userId,
        string $triggerSource,
        bool $createBackup,
    ): void {
        $hadSnapshot = $this->reader->readValid($tenant->id, TenantSeoFile::TYPE_SITEMAP_XML) !== null;

        $startedAt = now();
        $backupPath = null;
        $backupCreated = false;

        try {
            if ($hadSnapshot && $createBackup) {
                $current = $this->reader->readValid($tenant->id, TenantSeoFile::TYPE_SITEMAP_XML);
                if ($current !== null) {
                    $backup = $this->storage->createBackup($tenant->id, TenantSeoFile::TYPE_SITEMAP_XML, $current);
                    $backupPath = $backup['path'];
                    $backupCreated = true;
                }
            }

            $content = $this->sitemap->generateXml($tenant);
            $this->validateSitemapXml($content);
            $this->storage->writeSnapshot($tenant->id, TenantSeoFile::TYPE_SITEMAP_XML, $content);

            $this->upsertFileState(
                $tenant,
                TenantSeoFile::TYPE_SITEMAP_XML,
                $content,
                $userId,
                $triggerSource,
                $backupPath
            );

            $contentAt = $this->contentLastUpdated->lastUpdatedAt($tenant);
            TenantSeoFile::query()
                ->where('tenant_id', $tenant->id)
                ->where('type', TenantSeoFile::TYPE_SITEMAP_XML)
                ->update([
                    'last_public_content_change_at' => $contentAt,
                    'freshness_status' => $this->freshness->resolveStatus($tenant),
                    'last_checked_at' => now(),
                ]);

            $this->recordGeneration(
                $tenant,
                TenantSeoFile::TYPE_SITEMAP_XML,
                $triggerSource,
                $userId,
                true,
                TenantSeoFileGeneration::STATUS_SUCCESS,
                null,
                $startedAt,
                $backupCreated,
                $backupPath
            );
        } catch (Throwable $e) {
            $this->recordGeneration(
                $tenant,
                TenantSeoFile::TYPE_SITEMAP_XML,
                $triggerSource,
                $userId,
                true,
                TenantSeoFileGeneration::STATUS_FAILED,
                $e->getMessage(),
                $startedAt,
                $backupCreated,
                $backupPath
            );
            throw $e;
        }
    }

    private function recordGeneration(
        Tenant $tenant,
        string $type,
        string $triggerSource,
        ?int $userId,
        bool $overwriteConfirmed,
        string $status,
        ?string $errorMessage,
        CarbonInterface $startedAt,
        bool $backupCreated,
        ?string $backupPath,
    ): void {
        TenantSeoFileGeneration::query()->create([
            'tenant_id' => $tenant->id,
            'type' => $type,
            'status' => $status,
            'trigger_source' => $triggerSource,
            'triggered_by_user_id' => $userId,
            'overwrite_confirmed' => $overwriteConfirmed,
            'backup_created' => $backupCreated,
            'backup_storage_path' => $backupPath,
            'started_at' => $startedAt,
            'finished_at' => now(),
            'error_message' => $errorMessage,
        ]);
    }

    private function upsertFileState(
        Tenant $tenant,
        string $type,
        string $content,
        ?int $userId,
        string $source,
        ?string $backupStoragePath,
    ): void {
        $path = $this->storage->snapshotRelativePath($tenant->id, $type);
        $checksum = hash('sha256', $content);
        $size = strlen($content);
        $base = $this->canonical->resolve($tenant);
        $suffix = $type === TenantSeoFile::TYPE_ROBOTS_TXT ? '/robots.txt' : '/sitemap.xml';
        $publicUrl = $base.$suffix;

        TenantSeoFile::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'type' => $type,
            ],
            [
                'storage_disk' => $this->storage->diskName(),
                'storage_path' => $path,
                'public_url' => $publicUrl,
                'exists' => true,
                'checksum' => $checksum,
                'size_bytes' => $size,
                'generated_at' => now(),
                'last_checked_at' => now(),
                'last_generated_by_user_id' => $userId,
                'last_generation_source' => $source,
                'backup_storage_path' => $backupStoragePath,
            ]
        );
    }

    private function validateSitemapXml(string $xml): void
    {
        if (trim($xml) === '') {
            throw new InvalidArgumentException('Sitemap XML is empty.');
        }
        if (! str_contains($xml, 'urlset')) {
            throw new InvalidArgumentException('Sitemap XML is missing urlset.');
        }
    }
}
