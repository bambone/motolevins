<?php

namespace App\Services\Seo;

use App\Models\SeoMeta;

/**
 * Merges validated {@see SeoMeta::$json_ld} into a base @graph list (append-only policy).
 */
final class TenantSeoJsonLdOverrideMerger
{
    /**
     * @param  list<array<string, mixed>>  $baseGraph
     * @return list<array<string, mixed>>
     */
    public function merge(array $baseGraph, ?SeoMeta $seo): array
    {
        if ($seo === null) {
            return $baseGraph;
        }

        $raw = $seo->json_ld;
        if (! is_array($raw) || $raw === []) {
            return $baseGraph;
        }

        $extra = $this->normalizeOverride($raw);
        if ($extra === []) {
            return $baseGraph;
        }

        return array_merge($baseGraph, $extra);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function normalizeOverride(array $raw): array
    {
        if (isset($raw['@graph']) && is_array($raw['@graph'])) {
            return $this->filterObjects($raw['@graph']);
        }

        if (isset($raw['@type']) && is_string($raw['@type'])) {
            return [$this->onlyArrayShape($raw)];
        }

        return $this->filterObjects($raw);
    }

    /**
     * @param  array<int, mixed>  $items
     * @return list<array<string, mixed>>
     */
    private function filterObjects(array $items): array
    {
        $out = [];
        foreach ($items as $item) {
            if (is_array($item) && isset($item['@type']) && is_string($item['@type'])) {
                $out[] = $this->onlyArrayShape($item);
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function onlyArrayShape(array $item): array
    {
        $clean = [];
        foreach ($item as $k => $v) {
            if (! is_string($k) || $k === '') {
                continue;
            }
            if (is_array($v)) {
                $clean[$k] = $this->sanitizeNested($v);
            } elseif (is_scalar($v) || $v === null) {
                $clean[$k] = $v;
            }
        }

        return $clean;
    }

    /**
     * @param  array<int|string, mixed>  $v
     */
    private function sanitizeNested(array $v): mixed
    {
        $isList = array_is_list($v);
        $out = [];
        foreach ($v as $k => $x) {
            if (is_array($x)) {
                $out[$k] = $this->sanitizeNested($x);
            } elseif (is_scalar($x) || $x === null) {
                $out[$k] = $x;
            }
        }

        return $isList ? array_values($out) : $out;
    }
}
