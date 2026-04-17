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

        return route($def->filamentRouteName, [], true);
    }
}
