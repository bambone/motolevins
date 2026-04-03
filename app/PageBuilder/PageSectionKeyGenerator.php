<?php

namespace App\PageBuilder;

use App\Models\Page;

final class PageSectionKeyGenerator
{
    public function next(Page $page, string $typeId): string
    {
        $prefix = $typeId;
        $max = 0;
        foreach ($page->sections()->get(['section_key']) as $row) {
            $key = (string) $row->section_key;
            if (! str_starts_with($key, $prefix.'_')) {
                continue;
            }
            $suffix = substr($key, strlen($prefix) + 1);
            if (ctype_digit($suffix)) {
                $max = max($max, (int) $suffix);
            }
        }

        return $prefix.'_'.($max + 1);
    }
}
