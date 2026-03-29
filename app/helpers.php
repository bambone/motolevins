<?php

use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Models\TenantSetting;
use App\Services\Tenancy\TenantViewResolver;
use App\Tenant\CurrentTenant;
use Illuminate\Contracts\View\View;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

if (! function_exists('tenant')) {
    function tenant(): ?Tenant
    {
        if (! app()->bound(CurrentTenant::class)) {
            return null;
        }

        return app(CurrentTenant::class)->tenant;
    }
}

if (! function_exists('tenant_domain')) {
    function tenant_domain(): ?TenantDomain
    {
        if (! app()->bound(CurrentTenant::class)) {
            return null;
        }

        return app(CurrentTenant::class)->domain;
    }
}

if (! function_exists('is_non_tenant_host')) {
    function is_non_tenant_host(): bool
    {
        if (! app()->bound(CurrentTenant::class)) {
            return true;
        }

        return app(CurrentTenant::class)->isNonTenantHost;
    }
}

if (! function_exists('is_central_domain')) {
    /**
     * @deprecated Use is_non_tenant_host()
     */
    function is_central_domain(): bool
    {
        return is_non_tenant_host();
    }
}

if (! function_exists('currentTenant')) {
    function currentTenant(): ?Tenant
    {
        return tenant();
    }
}

if (! function_exists('tenant_view')) {
    /**
     * Resolve a tenant public view by logical name (theme + engine fallback) and return a View instance.
     *
     * @param  array<string, mixed>  $data
     */
    function tenant_view(string $logical, array $data = []): View
    {
        $resolved = app(TenantViewResolver::class)->resolve($logical);

        return view($resolved, $data);
    }
}

if (! function_exists('tenant_branding_asset_url')) {
    /**
     * Public URL for a tenant branding file stored on the public disk, or a legacy absolute URL.
     *
     * @param  non-empty-string|null  $relativePath  Path relative to storage/app/public (e.g. tenants/1/logo/x.png)
     */
    function tenant_branding_asset_url(?string $relativePath, ?string $legacyUrl): string
    {
        $relativePath = $relativePath !== null ? trim($relativePath) : '';
        if ($relativePath !== '') {
            $disk = Storage::disk('public');
            if (! $disk instanceof FilesystemAdapter) {
                return '';
            }

            return $disk->url(ltrim($relativePath, '/'));
        }

        $legacyUrl = $legacyUrl !== null ? trim($legacyUrl) : '';
        if ($legacyUrl !== '') {
            return $legacyUrl;
        }

        return '';
    }
}

if (! function_exists('tenant_branding_logo_url')) {
    function tenant_branding_logo_url(): string
    {
        $t = tenant();
        if ($t === null) {
            return '';
        }

        return tenant_branding_asset_url(
            TenantSetting::getForTenant($t->id, 'branding.logo_path', ''),
            TenantSetting::getForTenant($t->id, 'branding.logo', '')
        );
    }
}

if (! function_exists('tenant_branding_favicon_url')) {
    function tenant_branding_favicon_url(): string
    {
        $t = tenant();
        if ($t === null) {
            return '';
        }

        return tenant_branding_asset_url(
            TenantSetting::getForTenant($t->id, 'branding.favicon_path', ''),
            TenantSetting::getForTenant($t->id, 'branding.favicon', '')
        );
    }
}

if (! function_exists('tenant_branding_hero_url')) {
    function tenant_branding_hero_url(): string
    {
        $t = tenant();
        if ($t === null) {
            return '';
        }

        return tenant_branding_asset_url(
            TenantSetting::getForTenant($t->id, 'branding.hero_path', ''),
            TenantSetting::getForTenant($t->id, 'branding.hero', '')
        );
    }
}

if (! function_exists('platform_marketing_hero_headline')) {
    function platform_marketing_hero_headline(): string
    {
        $c = config('platform_marketing');
        $v = (string) ($c['hero_variant'] ?? 'c');

        return (string) ($c['hero'][$v] ?? $c['hero']['c'] ?? '');
    }
}

if (! function_exists('platform_marketing_demo_url')) {
    /**
     * URL для CTA «Посмотреть демо»: env или страница контактов.
     */
    function platform_marketing_demo_url(): string
    {
        $custom = trim((string) config('platform_marketing.demo_url', ''));
        if ($custom !== '') {
            return $custom;
        }

        if (Route::has('platform.contact')) {
            return route('platform.contact');
        }

        return '/contact';
    }
}
