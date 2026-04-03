<?php

namespace App\PageBuilder\Blueprints;

use App\PageBuilder\Contracts\PageSectionBlueprintInterface;

abstract class AbstractPageSectionBlueprint implements PageSectionBlueprintInterface
{
    public function supportsTheme(string $themeKey): bool
    {
        return in_array($themeKey, ['default', 'moto'], true);
    }

    protected function countNestedList(array $data, string $key): int
    {
        $items = $data[$key] ?? [];

        return is_array($items) ? count($items) : 0;
    }

    protected function stringPreview(array $data, string $key, int $maxLen = 80): string
    {
        $v = $data[$key] ?? '';

        if (! is_string($v)) {
            return '';
        }
        $v = trim($v);

        return $maxLen > 0 && strlen($v) > $maxLen ? substr($v, 0, $maxLen).'…' : $v;
    }
}
