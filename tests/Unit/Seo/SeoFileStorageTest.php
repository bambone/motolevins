<?php

namespace Tests\Unit\Seo;

use App\Models\TenantSeoFile;
use App\Services\Seo\SeoFileStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SeoFileStorageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(config('seo.disk', 'local'));
    }

    public function test_writes_snapshot_under_tenant_isolated_path(): void
    {
        $storage = app(SeoFileStorage::class);
        $storage->writeSnapshot(7, TenantSeoFile::TYPE_ROBOTS_TXT, "User-agent: *\nDisallow:\n");

        $path = $storage->snapshotRelativePath(7, TenantSeoFile::TYPE_ROBOTS_TXT);
        $this->assertTrue(Storage::disk($storage->diskName())->exists($path));
    }

    public function test_backup_uses_separate_directory(): void
    {
        $storage = app(SeoFileStorage::class);
        $info = $storage->createBackup(7, TenantSeoFile::TYPE_ROBOTS_TXT, 'old');

        $this->assertStringStartsWith('tenants/7/seo-backups/', $info['path']);
        $this->assertTrue(Storage::disk($storage->diskName())->exists($info['path']));
    }
}
