<?php

namespace App\Services\Tenancy;

use App\Models\Page;
use App\Models\PageSection;

/**
 * Синхронизация виртуального primary_html формы с page_sections.section_key = main.
 */
final class TenantPagePrimaryHtmlSync
{
    public function sync(Page $page, ?string $html): void
    {
        if ($page->slug === 'home') {
            return;
        }

        $existing = $page->sections()->where('section_key', 'main')->first();
        $base = [];
        if (is_array($existing?->data_json)) {
            $base = $existing->data_json;
        }
        $base['content'] = $html ?? '';

        PageSection::query()->updateOrCreate(
            [
                'tenant_id' => $page->tenant_id,
                'page_id' => $page->id,
                'section_key' => 'main',
            ],
            [
                'tenant_id' => $page->tenant_id,
                'title' => $existing?->title ?? 'Основной контент',
                'section_type' => $existing?->section_type ?? 'rich_text',
                'data_json' => $base,
                'sort_order' => $existing?->sort_order ?? 0,
                'status' => $existing?->status ?? 'published',
                'is_visible' => $existing?->is_visible ?? true,
            ]
        );
    }
}
