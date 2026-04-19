<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Support\TenantPwaChromeColors;
use App\Themes\ThemeRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantPublicPwaManifestController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $tenant = tenant();
        if ($tenant === null) {
            abort(404);
        }

        $push = $tenant->pushSettings;

        $name = $push?->pwa_name ?: $tenant->defaultPublicSiteName();
        $shortName = $push?->pwa_short_name ?: mb_substr($name, 0, 12);
        $startUrl = $push?->pwa_start_url ?: '/';
        $theme = TenantPwaChromeColors::themeColor($tenant);
        $bg = TenantPwaChromeColors::backgroundColor($tenant);
        $display = $push?->pwa_display ?: 'standalone';
        $icons = $push?->pwa_icons_json;
        if (! is_array($icons) || $icons === []) {
            $icons = $this->defaultIconsForTenant($tenant);
        }

        $id = $tenant->id ? '/?pwa_id='.$tenant->id : '/';

        $payload = [
            'id' => $id,
            'name' => $name,
            'short_name' => $shortName,
            'start_url' => $startUrl,
            'scope' => '/',
            'display' => $display,
            'theme_color' => $theme,
            'background_color' => $bg,
            'icons' => $icons,
        ];

        return response()
            ->json($payload, 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ->header('Content-Type', 'application/manifest+json')
            ->header('Cache-Control', 'public, max-age=300')
            ->header('Vary', 'Host');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function defaultIconsForTenant(Tenant $tenant): array
    {
        $registry = app(ThemeRegistry::class);
        $tk = $tenant->themeKey();

        return [
            [
                'src' => $registry->assetUrl($tk, 'icons/icon-192.png', $tenant),
                'sizes' => '192x192',
                'type' => 'image/png',
            ],
            [
                'src' => $registry->assetUrl($tk, 'icons/icon-512.png', $tenant),
                'sizes' => '512x512',
                'type' => 'image/png',
            ],
            [
                'src' => $registry->assetUrl($tk, 'icons/icon-maskable.png', $tenant),
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'maskable',
            ],
        ];
    }
}
