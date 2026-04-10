<?php

namespace App\Http\Controllers;

use App\Models\LocationLandingPage;

class LocationLandingController extends Controller
{
    public function show(string $slug)
    {
        abort_if(tenant() === null, 404);

        $page = LocationLandingPage::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        return tenant_view('tenant.pages.location-landing', [
            'page' => $page,
        ]);
    }
}
