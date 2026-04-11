<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ResolvesTenantArgument;
use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Tenant\Expert\ExpertAutoProgramCoverRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Приёмка: главная витрина тенанта отдаёт 7 desktop WebP обложек программ на CDN, размер и RIFF-WEBP.
 */
final class TenantAcceptProgramCoverBundleCommand extends Command
{
    use ResolvesTenantArgument;

    protected $signature = 'tenant:accept-program-cover-bundle
                            {tenant=aflyatunov : slug или id тенанта}
                            {--min-kb= : Минимальный размер desktop WebP (КБ), по умолчанию 40}';

    protected $description = 'Проверить обложки expert_auto: HTML главной + загрузка WebP с CDN';

    public function handle(): int
    {
        $tenant = $this->resolveTenant((string) $this->argument('tenant'));

        if ($tenant->theme_key !== ExpertAutoProgramCoverRegistry::THEME_KEY) {
            $this->error('Тенант не expert_auto.');

            return self::FAILURE;
        }

        $baseUrl = $this->publicHomeUrl($tenant);
        if ($baseUrl === null) {
            $this->error('Нет домена тенанта (tenant_domains).');

            return self::FAILURE;
        }

        $this->info("GET {$baseUrl}");

        $response = Http::timeout(30)->withHeaders(['Accept' => 'text/html'])->get($baseUrl);
        if (! $response->successful()) {
            $this->error('Главная: HTTP '.$response->status());

            return self::FAILURE;
        }

        $html = $response->body();
        $cdn = rtrim((string) config('tenant_storage.public_cdn_base_url', ''), '/');
        if ($cdn === '') {
            $this->error('Задайте TENANT_STORAGE_PUBLIC_CDN_URL для приёмки по абсолютным URL в HTML.');

            return self::FAILURE;
        }

        $tid = (int) $tenant->id;
        $pattern = '#('.preg_quote($cdn, '#').'/tenants/'.$tid.'/public/site/expert_auto/programs/([a-z0-9-]+)/card-cover-desktop\.webp[^"\s>]*)#';

        if (! preg_match_all($pattern, $html, $matches)) {
            $this->error('В HTML нет desktop URL обложек на CDN.');

            return self::FAILURE;
        }

        $bySlug = [];
        foreach ($matches[1] as $i => $url) {
            $slug = $matches[2][$i];
            $bySlug[$slug] ??= $url;
        }

        $expected = array_keys(ExpertAutoProgramCoverRegistry::relativeFilesByProgramSlug());
        sort($expected);
        $got = array_keys($bySlug);
        sort($got);
        if ($got !== $expected) {
            $this->error('Набор slug: ожидалось '.implode(',', $expected).', факт '.implode(',', $got));

            return self::FAILURE;
        }

        $minKb = (int) ($this->option('min-kb') ?? 40);
        if ($minKb < 1) {
            $minKb = 40;
        }
        $minBytes = $minKb * 1024;

        foreach ($expected as $slug) {
            $url = $bySlug[$slug];
            $img = Http::timeout(45)->withHeaders(['Accept' => 'image/webp,*/*'])->get($url);
            if (! $img->successful()) {
                $this->error("{$slug}: HTTP {$img->status()} {$url}");

                return self::FAILURE;
            }
            $body = $img->body();
            $len = strlen($body);
            if ($len < $minBytes) {
                $this->error("{$slug}: размер {$len} < {$minBytes} bytes — похоже на пустой/старый кэш или плейсхолдер");

                return self::FAILURE;
            }
            if ($len < 12 || substr($body, 0, 4) !== 'RIFF' || substr($body, 8, 4) !== 'WEBP') {
                $this->error("{$slug}: не бинарный WebP");

                return self::FAILURE;
            }
            $this->line("OK {$slug} {$len} bytes");
        }

        if (! str_contains($html, '?v=')) {
            $this->warn('В URL нет ?v= — при смене файла под тем же ключом проверьте CDN cache / TENANT_STORAGE_PUBLIC_URL_VERSION.');
        }

        $this->info('PASS: 7 desktop WebP, slug полный, размеры в норме.');

        return self::SUCCESS;
    }

    private function publicHomeUrl(Tenant $tenant): ?string
    {
        $domain = $tenant->domains()
            ->where('status', TenantDomain::STATUS_ACTIVE)
            ->orderByDesc('is_primary')
            ->first()
            ?? $tenant->primaryDomain();

        if ($domain === null || ! filled($domain->host)) {
            return null;
        }

        $host = strtolower(trim((string) $domain->host));
        $scheme = $this->guessHttpScheme($host);

        return $scheme.'://'.$host.'/';
    }

    private function guessHttpScheme(string $host): string
    {
        if ($host === 'localhost' || str_starts_with($host, '127.')) {
            return 'http';
        }
        if (str_ends_with($host, '.local')) {
            return 'http';
        }

        return 'https';
    }
}
