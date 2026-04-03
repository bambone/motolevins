<?php

namespace App\Services\Tenancy;

use App\Models\Page;
use App\Models\Tenant;
use Illuminate\Support\Collection;

/**
 * Пункты верхнего меню публичного сайта тенанта (только CMS-страницы из БД).
 */
final class TenantMainMenuPages
{
    /**
     * @return Collection<int, array{label: string, url: string}>
     */
    public function menuItems(Tenant $tenant): Collection
    {
        return Page::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'published')
            ->where('show_in_main_menu', true)
            ->where('slug', '!=', 'home')
            ->orderBy('main_menu_sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Page $page): array => [
                'label' => $page->name,
                'url' => route('page.show', ['slug' => $page->slug]),
            ]);
    }
}
