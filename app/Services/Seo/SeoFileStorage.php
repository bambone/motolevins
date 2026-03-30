<?php

namespace App\Services\Seo;

use App\Models\Tenant;
use App\Models\TenantSeoFile;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class SeoFileStorage
{
    public function diskName(): string
    {
        return (string) config('seo.disk', 'local');
    }

    public function disk(): Filesystem
    {
        return Storage::disk($this->diskName());
    }

    public function snapshotRelativePath(int $tenantId, string $type): string
    {
        $file = $type === TenantSeoFile::TYPE_ROBOTS_TXT ? 'robots.txt' : 'sitemap.xml';

        return 'tenants/'.$tenantId.'/seo/'.$file;
    }

    public function snapshotExistsOnDisk(int $tenantId, string $type): bool
    {
        return $this->disk()->exists($this->snapshotRelativePath($tenantId, $type));
    }

    public function readSnapshot(int $tenantId, string $type): ?string
    {
        $path = $this->snapshotRelativePath($tenantId, $type);
        if (! $this->disk()->exists($path)) {
            return null;
        }

        $raw = $this->disk()->get($path);

        return is_string($raw) ? $raw : null;
    }

    /**
     * @return array{path: string, filename: string}
     */
    public function createBackup(int $tenantId, string $type, string $currentContent): array
    {
        $stamp = CarbonImmutable::now()->format('Ymd-His');
        $name = $type === TenantSeoFile::TYPE_ROBOTS_TXT
            ? "robots-{$stamp}.txt.bak"
            : "sitemap-{$stamp}.xml.bak";
        $relative = 'tenants/'.$tenantId.'/seo-backups/'.$name;
        $ok = $this->disk()->put($relative, $currentContent);
        if (! $ok) {
            throw new RuntimeException('Failed to write SEO backup file.');
        }

        return ['path' => $relative, 'filename' => $name];
    }

    public function writeSnapshot(int $tenantId, string $type, string $content): void
    {
        $path = $this->snapshotRelativePath($tenantId, $type);
        $dir = dirname($path);
        if (! $this->disk()->exists($dir)) {
            $this->disk()->makeDirectory($dir);
        }
        $ok = $this->disk()->put($path, $content);
        if (! $ok) {
            throw new RuntimeException('Failed to write SEO snapshot file.');
        }
    }

    public function publicUrlForPath(Tenant $tenant, string $relativeFile): string
    {
        $base = app(TenantCanonicalPublicBaseUrl::class)->resolve($tenant);

        return $base.'/'.ltrim($relativeFile, '/');
    }
}
