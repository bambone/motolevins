<?php

namespace App\Http\Controllers;

use App\Models\SeoLandingPage;

class SeoLandingPageController extends Controller
{
    public function show(string $slug)
    {
        abort_if(tenant() === null, 404);

        $page = SeoLandingPage::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        return tenant_view('tenant.pages.seo-landing', [
            'page' => $page,
        ]);
    }
}
