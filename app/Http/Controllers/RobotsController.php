<?php

namespace App\Http\Controllers;

use App\Services\Seo\TenantSeoPublicContentService;
use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __invoke(TenantSeoPublicContentService $seo): Response
    {
        $tenant = currentTenant();
        abort_if($tenant === null, 404);

        $body = $seo->robotsBody($tenant);

        return response($body, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
