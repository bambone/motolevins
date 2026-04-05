<?php

namespace App\Http\Controllers;

use App\Models\PlatformSetting;
use App\Services\Seo\PlatformMarketingLlmsGenerator;
use App\Services\Seo\PlatformMarketingSitemapXml;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PlatformMarketingSitemapController extends Controller
{
    public function __invoke(
        Request $request,
        PlatformMarketingSitemapXml $xml,
        PlatformMarketingLlmsGenerator $pathsSource,
    ): Response {
        $base = rtrim($request->getSchemeAndHttpHost(), '/');

        $paths = PlatformSetting::get('marketing.seo.sitemap_paths', null);
        if (! is_array($paths) || $paths === []) {
            $paths = $pathsSource->defaultPaths();
        } else {
            $paths = array_values(array_filter(array_map('strval', $paths), fn (string $p): bool => $p !== ''));
        }

        $document = $xml->build($base, $paths);

        return new Response($document, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
