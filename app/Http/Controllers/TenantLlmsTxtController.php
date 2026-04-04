<?php

namespace App\Http\Controllers;

use App\Services\Seo\FallbackSeoGenerator;
use App\Services\Seo\TenantCanonicalPublicBaseUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TenantLlmsTxtController extends Controller
{
    public function __invoke(Request $request, TenantCanonicalPublicBaseUrl $canonical, FallbackSeoGenerator $branding): Response
    {
        $tenant = tenant();
        abort_if($tenant === null, 404);

        $base = rtrim($canonical->resolve($tenant), '/');
        $siteName = $branding->siteName($tenant);
        $lines = [
            '# '.$siteName,
            '',
            'Публичный сайт аренды мототехники (tenant). Экспериментальный llms.txt; не заменяет sitemap.xml и HTML.',
            '',
            '## Key pages',
        ];

        $paths = config('seo_sitemap.llms_paths', []);
        $paths = is_array($paths) ? $paths : [];
        foreach ($paths as $path) {
            $p = trim((string) $path);
            if ($p === '') {
                continue;
            }
            $url = $p === '/' ? $base.'/' : $base.$p;
            $lines[] = '- '.$url;
        }

        $lines[] = '';
        $lines[] = 'Sitemap: '.$base.'/sitemap.xml';

        $body = implode("\n", $lines);
        $response = new Response($body, 200);
        $response->headers->set('Content-Type', 'text/plain; charset=UTF-8');

        return $response;
    }
}
