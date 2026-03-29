<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PlatformMarketingSitemapController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $base = rtrim($request->getSchemeAndHttpHost(), '/');
        $paths = [
            '/',
            '/features',
            '/pricing',
            '/faq',
            '/contact',
            '/for-moto-rental',
            '/for-car-rental',
            '/for-services',
        ];

        $urls = [];
        foreach ($paths as $path) {
            $urls[] = [
                'loc' => $base.$path,
                'changefreq' => $path === '/' ? 'daily' : 'weekly',
                'priority' => $path === '/' ? '1.0' : '0.8',
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
        $xml = implode("\n", $parts);

        return new Response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
