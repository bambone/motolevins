<?php

namespace App\Services\Seo;

/**
 * sitemap.xml for platform marketing (URL list + changefreq/priority heuristics).
 */
final class PlatformMarketingSitemapXml
{
    /**
     * @param  list<string>  $paths
     */
    public function build(string $baseUrl, array $paths): string
    {
        $base = rtrim($baseUrl, '/');
        $urls = [];
        foreach ($paths as $path) {
            $p = trim((string) $path);
            if ($p === '') {
                continue;
            }
            $urls[] = [
                'loc' => $p === '/' ? $base.'/' : $base.$p,
                'changefreq' => $p === '/' ? 'daily' : 'weekly',
                'priority' => $p === '/' ? '1.0' : '0.8',
            ];
        }

        $parts = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];
        foreach ($urls as $u) {
            $loc = htmlspecialchars($u['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $parts[] = '  <url>';
            $parts[] = '    <loc>'.$loc.'</loc>';
            $parts[] = '    <changefreq>'.htmlspecialchars($u['changefreq'], ENT_XML1 | ENT_QUOTES, 'UTF-8').'</changefreq>';
            $parts[] = '    <priority>'.htmlspecialchars($u['priority'], ENT_XML1 | ENT_QUOTES, 'UTF-8').'</priority>';
            $parts[] = '  </url>';
        }
        $parts[] = '</urlset>';

        return implode("\n", $parts);
    }
}
