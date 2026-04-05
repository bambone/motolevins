<?php

namespace App\Http\Controllers;

use App\Models\PlatformSetting;
use App\Services\Seo\PlatformMarketingRobotsBody;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PlatformMarketingRobotsController extends Controller
{
    public function __invoke(Request $request, PlatformMarketingRobotsBody $body): Response
    {
        $sitemap = rtrim($request->getSchemeAndHttpHost(), '/').'/sitemap.xml';

        if ((bool) PlatformSetting::get('marketing.seo.custom_robots_enabled', false)) {
            $custom = trim((string) PlatformSetting::get('marketing.seo.robots_txt', ''));
            if ($custom !== '') {
                return new Response($custom, 200, [
                    'Content-Type' => 'text/plain; charset=UTF-8',
                ]);
            }
        }

        $text = $body->build($sitemap);

        return new Response($text, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
