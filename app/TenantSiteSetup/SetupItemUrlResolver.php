<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

use App\Filament\Tenant\Resources\PageResource;
use App\Models\Page;
use App\Models\Tenant;
use Illuminate\Support\Facades\Route;

final class SetupItemUrlResolver
{
    public function urlFor(Tenant $tenant, SetupItemDefinition $def): ?string
    {
        if ($def->key === 'programs.first_published_program') {
            if (Route::has('filament.admin.resources.tenant-service-programs.create')) {
                return route('filament.admin.resources.tenant-service-programs.create', [], true);
            }

            return Route::has('filament.admin.resources.tenant-service-programs.index')
                ? route('filament.admin.resources.tenant-service-programs.index', [], true)
                : null;
        }

        if ($def->key === 'pages.home.hero_title' || $def->key === 'pages.home.hero_cta_or_contact_block') {
            $page = Page::query()
                ->where('tenant_id', $tenant->id)
                ->where('slug', 'home')
                ->first();
            if ($page === null) {
                return PageResource::getUrl('index');
            }

            return PageResource::getUrl('edit', ['record' => $page]);
        }

        if ($def->filamentRouteName === null) {
            return null;
        }

        if (! Route::has($def->filamentRouteName)) {
            return null;
        }

        $url = route($def->filamentRouteName, [], true);

        if ($def->settingsTabKey !== null
            && $def->filamentRouteName === 'filament.admin.pages.settings') {
            $sep = str_contains($url, '?') ? '&' : '?';

            return $url.$sep.'settings_tab='.rawurlencode($def->settingsTabKey);
        }

        return $url;
    }
}
