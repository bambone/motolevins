<?php

namespace App\Http\Controllers;

use App\Models\TenantSetting;
use App\Services\Seo\TenantSeoPublicContentService;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(TenantSeoPublicContentService $seo): Response
    {
        $tenant = currentTenant();
        abort_if($tenant === null, 404);

        $enabled = (bool) TenantSetting::getForTenant($tenant->id, 'seo.sitemap_enabled', true);
        if (! $enabled) {
            abort(404);
        }

        $body = $seo->sitemapBodyForEnabledTenant($tenant);

        return response($body, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
