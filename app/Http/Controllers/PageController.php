<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Services\CustomPageResolver;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    /**
     * Nested URL under /services/{segment} maps to page slug {@code services/{segment}}.
     */
    public function showServiceNested(string $nestedSlug, CustomPageResolver $resolver): View
    {
        return $this->renderPublishedPage('services/'.$nestedSlug, $resolver);
    }

    public function show(string $slug, CustomPageResolver $resolver): View
    {
        return $this->renderPublishedPage($slug, $resolver);
    }

    private function renderPublishedPage(string $slug, CustomPageResolver $resolver): View
    {
        $page = Page::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        $viewName = $resolver->resolveView($page->slug);

        return tenant_view($viewName, [
            'page' => $page,
        ]);
    }
}
