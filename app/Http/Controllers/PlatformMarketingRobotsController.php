<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PlatformMarketingRobotsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $sitemap = rtrim($request->getSchemeAndHttpHost(), '/').'/sitemap.xml';
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
            'Sitemap: '.$sitemap,
            '',
        ];

        return new Response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
