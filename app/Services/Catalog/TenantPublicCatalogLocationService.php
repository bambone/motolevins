<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Models\TenantLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Публичный выбор локации: query ?location=slug и сессия (запоминается middleware).
 */
final class TenantPublicCatalogLocationService
{
    public const string SESSION_KEY = 'public_catalog_location_slug';

    public const string QUERY_KEY = 'location';

    public function rememberFromRequestIfValid(Request $request): void
    {
        $tenant = currentTenant();
        if ($tenant === null) {
            return;
        }
        $slug = $request->query(self::QUERY_KEY);
        if (! is_string($slug) || $slug === '') {
            return;
        }
        if ($slug === 'all' || $slug === '_all') {
            $request->session()->forget(self::SESSION_KEY);

            return;
        }
        $exists = TenantLocation::query()
            ->where('tenant_id', $tenant->id)
            ->where('slug', $slug)
            ->where('is_active', true)
            ->exists();
        if ($exists) {
            $request->session()->put(self::SESSION_KEY, $slug);
        }
    }

    public function clearSession(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
    }

    public function resolve(?Request $request = null): ?TenantLocation
    {
        $request ??= request();
        $tenant = currentTenant();
        if ($tenant === null) {
            return null;
        }

        $slug = $request->query(self::QUERY_KEY);
        if (is_string($slug) && $slug !== '') {
            $loc = TenantLocation::query()
                ->where('tenant_id', $tenant->id)
                ->where('slug', $slug)
                ->where('is_active', true)
                ->first();
            if ($loc !== null) {
                return $loc;
            }
        }

        $sessionSlug = $request->session()->get(self::SESSION_KEY);
        if (is_string($sessionSlug) && $sessionSlug !== '') {
            return TenantLocation::query()
                ->where('tenant_id', $tenant->id)
                ->where('slug', $sessionSlug)
                ->where('is_active', true)
                ->first();
        }

        return null;
    }

    /**
     * @return Collection<int, TenantLocation>
     */
    public function activeLocationsForCurrentTenant()
    {
        $tenant = currentTenant();
        if ($tenant === null) {
            return collect();
        }

        return TenantLocation::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
