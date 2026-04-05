<?php

namespace App\Services\Seo;

/**
 * Default robots.txt body for platform marketing hosts (live generation, not snapshot).
 */
final class PlatformMarketingRobotsBody
{
    public function build(string $sitemapAbsoluteUrl): string
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /platform',
            'Disallow: /api',
            '',
            '# Explicit allow for major search / AI crawlers (same private paths as *)',
            'User-agent: OAI-SearchBot',
            'User-agent: GPTBot',
            'User-agent: Googlebot',
            'User-agent: Google-Extended',
            'User-agent: Bingbot',
            'User-agent: PerplexityBot',
            'User-agent: ClaudeBot',
            'User-agent: Claude-Web',
            'User-agent: CCBot',
            'User-agent: Yandex',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /platform',
            'Disallow: /api',
            '',
            'Sitemap: '.$sitemapAbsoluteUrl,
            '',
        ];

        return implode("\n", $lines);
    }
}
