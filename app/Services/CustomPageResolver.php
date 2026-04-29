<?php

namespace App\Services;

use App\Models\Tenant;
use App\Services\Tenancy\TenantViewResolver;
use Illuminate\Support\Str;

class CustomPageResolver
{
    public function __construct(
        private readonly TenantViewResolver $viewResolver
    ) {}

    /**
     * Resolves the logical view name for a given page slug, honoring tenant theme.
     *
     * @return string The logical name (e.g. 'pages.custom.contacts' or 'pages.page')
     */
    public function resolveView(string $slug, ?Tenant $tenant = null): string
    {
        // Slugs may contain "/" for nested public paths (e.g. services/media-outreach). Normalize for logical view lookup.
        $normalized = strtolower(trim(str_replace('\\', '/', $slug), '/'));
        if ($normalized === '') {
            return 'pages.page';
        }
        // Path-safe custom view id: slashes → hyphens (pages.custom.foo-bar-baz).
        $viewSlug = preg_replace('#/+#', '-', $normalized);
        $viewSlug = (string) Str::slug($viewSlug);

        if ($viewSlug === '') {
            return 'pages.page';
        }

        $customLogicalName = "pages.custom.{$viewSlug}";

        if ($this->viewResolver->exists($customLogicalName, $tenant)) {
            return $customLogicalName;
        }

        return 'pages.page';
    }
}
