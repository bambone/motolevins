<?php

namespace App\Services\Seo;

use App\Models\Tenant;
use App\Models\TenantSetting;
use InvalidArgumentException;

final class RobotsTxtGenerator
{
    public function __construct(
        private TenantCanonicalPublicBaseUrl $canonicalBase,
    ) {}

    public function generate(Tenant $tenant): string
    {
        $id = $tenant->id;

        $indexingEnabled = (bool) TenantSetting::getForTenant($id, 'seo.indexing_enabled', true);
        if (! $indexingEnabled) {
            return "User-agent: *\nDisallow: /\n";
        }

        $rawCustom = trim((string) TenantSetting::getForTenant($id, 'seo.robots_txt', ''));
        $customFlag = TenantSetting::getForTenant($id, 'seo.custom_robots_enabled', null);
        if ($customFlag === null) {
            $useCustom = $rawCustom !== '';
        } else {
            $useCustom = (bool) $customFlag && $rawCustom !== '';
        }

        if ($useCustom) {
            $sanitized = $this->sanitizeContent($rawCustom);
            if ($sanitized === '') {
                return $this->buildTemplate($tenant);
            }

            return $sanitized;
        }

        return $this->buildTemplate($tenant);
    }

    /**
     * Validates content before persisting a snapshot; throws if empty after normalize.
     */
    public function validateForSnapshot(string $content): string
    {
        $sanitized = $this->sanitizeContent($content);
        if ($sanitized === '') {
            throw new InvalidArgumentException('robots.txt content is empty after normalization.');
        }

        return $sanitized;
    }

    private function buildTemplate(Tenant $tenant): string
    {
        $id = $tenant->id;
        $lines = ['User-agent: *'];

        $allowPaths = TenantSetting::getForTenant($id, 'seo.robots_allow_paths', null);
        if (! is_array($allowPaths) || $allowPaths === []) {
            $allowPaths = ['/'];
        }
        foreach ($allowPaths as $path) {
            $p = $this->normalizePathRule((string) $path);
            if ($p !== '') {
                $lines[] = 'Allow: '.$p;
            }
        }

        $disallowPaths = TenantSetting::getForTenant($id, 'seo.robots_disallow_paths', null);
        if (! is_array($disallowPaths) || $disallowPaths === []) {
            $disallowPaths = ['/admin', '/api'];
        }
        foreach ($disallowPaths as $path) {
            $p = $this->normalizePathRule((string) $path);
            if ($p !== '') {
                $lines[] = 'Disallow: '.$p;
            }
        }

        $includeSitemap = (bool) TenantSetting::getForTenant($id, 'seo.robots_include_sitemap', true);
        $sitemapEnabled = (bool) TenantSetting::getForTenant($id, 'seo.sitemap_enabled', true);
        if ($includeSitemap && $sitemapEnabled) {
            $base = $this->canonicalBase->resolve($tenant);
            $sitemapUrl = $base.'/sitemap.xml';
            if (! filter_var($sitemapUrl, FILTER_VALIDATE_URL)) {
                throw new InvalidArgumentException('Invalid sitemap URL for robots template.');
            }
            $lines[] = '';
            $lines[] = 'Sitemap: '.$sitemapUrl;
        }

        return implode("\n", $lines)."\n";
    }

    private function normalizePathRule(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }
        if ($path[0] !== '/') {
            $path = '/'.$path;
        }

        return $path;
    }

    private function sanitizeContent(string $content): string
    {
        $content = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $content) ?? '';
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        return trim($content);
    }
}
