<?php

namespace App\Services\Seo;

/**
 * Reads {@see config('seo_routes.routes')} for tenant public route SEO templates.
 */
final class SeoRouteRegistry
{
    /**
     * @return array<string, mixed>|null
     */
    public function get(string $routeName): ?array
    {
        $routes = config('seo_routes.routes', []);

        if (! is_array($routes) || $routes === []) {
            return null;
        }

        $row = $routes[$routeName] ?? null;

        return is_array($row) ? $row : null;
    }

    /**
     * Interpolate `{key}` placeholders in string values of a row.
     *
     * @param  array<string, mixed>  $row
     * @param  array<string, string>  $vars
     * @return array<string, mixed>
     */
    public function interpolateRow(array $row, array $vars): array
    {
        $out = [];
        foreach ($row as $key => $value) {
            if (is_string($value)) {
                $out[$key] = $this->interpolateString($value, $vars);
            } else {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    private function interpolateString(string $template, array $vars): string
    {
        return (string) preg_replace_callback('/\{([a-zA-Z0-9_]+)\}/', function (array $m) use ($vars): string {
            $k = $m[1];

            return $vars[$k] ?? '';
        }, $template);
    }
}
