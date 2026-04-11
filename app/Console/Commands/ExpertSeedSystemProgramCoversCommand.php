<?php

namespace App\Console\Commands;

use App\Support\Storage\TenantStorage;
use App\Support\Storage\TenantStorageDisks;
use App\Tenant\Expert\ExpertAutoProgramCoverInstaller;
use App\Tenant\Expert\ExpertAutoProgramCoverRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Одноразовая/операционная заливка пресетов WebP в системный пул темы (как bundled moto).
 * Дальше любой тенант с expert_auto: {@see ExpertAutoProgramCoverInstaller}.
 */
class ExpertSeedSystemProgramCoversCommand extends Command
{
    protected $signature = 'expert:seed-system-program-covers {--dry-run : Только список ключей}';

    protected $description = 'WebP пресеты → tenants/_system/themes/expert_auto/program-covers/*.webp (публичный диск R2/local)';

    public function handle(): int
    {
        if (! function_exists('imagecreatetruecolor') || ! function_exists('imagewebp')) {
            $this->error('Нужен PHP GD с imagewebp.');

            return self::FAILURE;
        }

        $diskName = TenantStorageDisks::publicDiskName();
        $disk = Storage::disk($diskName);
        $dry = (bool) $this->option('dry-run');
        $n = 0;

        foreach (ExpertAutoProgramCoverRegistry::relativeFilesByProgramSlug() as $slug => $files) {
            foreach (['desktop' => [1200, 640], 'mobile' => [720, 1040]] as $kind => [$w, $h]) {
                $filename = $kind === 'desktop' ? $files['desktop'] : $files['mobile'];
                $key = TenantStorage::systemBundledThemeObjectKey(
                    ExpertAutoProgramCoverRegistry::THEME_KEY,
                    'program-covers/'.$filename,
                );
                $this->line($key);
                $n++;
                if ($dry) {
                    continue;
                }
                $bytes = $this->renderNeutralGradientWebp($w, $h, $slug.'|'.$kind);
                if ($bytes === null || $bytes === '') {
                    $this->error("Не удалось сгенерировать: {$filename}");

                    return self::FAILURE;
                }
                $disk->put($key, $bytes, TenantStorage::mergedOptionsForPublicObjectWrite($disk, [
                    'visibility' => 'public',
                    'ContentType' => 'image/webp',
                ]));
            }
        }

        $this->info(($dry ? '[dry-run] ' : '')."Ключей: {$n} → disk `{$diskName}`");
        if (! $dry) {
            $this->line('Далее: php artisan tenant:sync-program-cover-bundle {slug|id}');
        }

        return self::SUCCESS;
    }

    /**
     * Плейсхолдер: чуть светлее фона карточки + лёгкие диагонали (читается как обложка, а не «пустой блок»).
     * Замените файлами темы через theme:push-system-bundled при необходимости.
     */
    private function renderNeutralGradientWebp(int $w, int $h, string $seed): ?string
    {
        $im = imagecreatetruecolor($w, $h);
        if ($im === false) {
            return null;
        }
        imagealphablending($im, true);

        $crc = crc32($seed);
        $r1 = 22 + ($crc % 18);
        $g1 = 26 + (($crc >> 3) % 20);
        $b1 = 38 + (($crc >> 6) % 22);
        $r2 = 12 + (($crc >> 9) % 14);
        $g2 = 14 + (($crc >> 12) % 16);
        $b2 = 22 + (($crc >> 15) % 18);

        for ($y = 0; $y < $h; $y++) {
            $t = $h > 1 ? $y / ($h - 1) : 0.0;
            $r = (int) round($r1 + ($r2 - $r1) * $t);
            $g = (int) round($g1 + ($g2 - $g1) * $t);
            $b = (int) round($b1 + ($b2 - $b1) * $t);
            $c = imagecolorallocate($im, max(0, min(255, $r)), max(0, min(255, $g)), max(0, min(255, $b)));
            imageline($im, 0, $y, $w - 1, $y, $c);
        }

        $stride = 48 + ($crc % 40);
        for ($i = -$h; $i < $w + $h; $i += $stride) {
            $a = 72 + (($crc + $i * 17) % 40);
            $line = imagecolorallocatealpha($im, 210, 180, 140, min(127, $a));
            imageline($im, $i, 0, $i + (int) round($h * 1.15), $h, $line);
        }
        $stride2 = 64 + (($crc >> 8) % 48);
        for ($i = -$h; $i < $w + $h; $i += $stride2) {
            $line = imagecolorallocatealpha($im, 255, 255, 255, 105);
            imageline($im, $i + 12, 0, $i + 12 + (int) round($h * 1.05), $h, $line);
        }

        ob_start();
        $ok = imagewebp($im, null, 88);
        imagedestroy($im);
        $buf = ob_get_clean();

        return $ok && is_string($buf) && $buf !== '' ? $buf : null;
    }
}
