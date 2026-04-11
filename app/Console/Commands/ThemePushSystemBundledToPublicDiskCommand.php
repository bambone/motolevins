<?php

namespace App\Console\Commands;

use App\Support\Storage\TenantStorage;
use App\Support\Storage\TenantStorageArea;
use App\Support\Storage\TenantStorageDisks;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Заливает {@code resources/themes/{key}/public/…} в публичный диск под {@code tenants/_system/themes/{key}/…}.
 *
 * Переопределения клиента — отдельно: {@code tenants/{id}/public/themes/…} (см. {@see TenantStorageArea::PublicThemes}).
 */
class ThemePushSystemBundledToPublicDiskCommand extends Command
{
    protected $signature = 'theme:push-system-bundled
                            {theme_key=moto : Ключ темы (каталог resources/themes/{key}/public)}
                            {--dry-run : Только список ключей}';

    protected $description = 'Upload bundled theme assets to public disk at tenants/_system/themes/{key}/ (R2/S3 or local)';

    public function handle(): int
    {
        $key = strtolower(trim((string) $this->argument('theme_key')));
        if ($key === '' || ! preg_match('/^[a-z0-9][a-z0-9_-]{0,62}$/', $key)) {
            $this->error('Некорректный theme_key.');

            return self::FAILURE;
        }

        $src = resource_path('themes/'.$key.'/public');
        if (! is_dir($src)) {
            $this->error('Нет каталога: '.$src.' — выполните theme:publish-bundled или добавьте файлы темы.');

            return self::FAILURE;
        }

        $diskName = TenantStorageDisks::publicDiskName();
        $disk = Storage::disk($diskName);
        $dry = (bool) $this->option('dry-run');
        $prefix = TenantStorage::SYSTEM_POOL_PREFIX.'/themes/'.$key;
        $srcNorm = str_replace('\\', '/', rtrim($src, '/\\'));

        $n = 0;
        foreach (File::allFiles($src) as $file) {
            $pathname = str_replace('\\', '/', $file->getPathname());
            $rel = ltrim(str_replace($srcNorm, '', $pathname), '/');
            $objectKey = $prefix.'/'.$rel;
            $this->line($objectKey);
            $n++;
            if ($dry) {
                continue;
            }
            $disk->put(
                $objectKey,
                File::get($pathname),
                TenantStorage::mergedOptionsForPublicObjectWrite($disk, ['visibility' => 'public']),
            );
        }

        if ($n === 0) {
            $this->warn('Нет файлов в '.$src);

            return self::FAILURE;
        }

        $this->info(($dry ? '[dry-run] ' : '')."Файлов: {$n} → disk `{$diskName}`");

        return self::SUCCESS;
    }
}
