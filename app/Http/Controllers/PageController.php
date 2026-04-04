<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Services\CustomPageResolver;

class PageController extends Controller
{
    public function show(string $slug, CustomPageResolver $resolver)
    {
        $page = Page::where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        $viewName = $resolver->resolveView($page->slug);

        return tenant_view($viewName, [
            'page' => $page,
        ]);
    }
}
