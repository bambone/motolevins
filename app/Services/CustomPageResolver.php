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
        // Must normalize slug and prevent path traversal
        $slug = Str::slug($slug);

        if (empty($slug)) {
            return 'pages.page';
        }

        $customLogicalName = "pages.custom.{$slug}";

        if ($this->viewResolver->exists($customLogicalName, $tenant)) {
            return $customLogicalName;
        }

        return 'pages.page';
    }
}
