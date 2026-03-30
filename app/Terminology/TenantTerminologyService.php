<?php

namespace App\Terminology;

use App\Models\DomainLocalizationPresetTerm;
use App\Models\DomainTerm;
use App\Models\Tenant;
use App\Models\TenantTermOverride;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class TenantTerminologyService
{
    private const CACHE_VERSION = 'v3';

    public function label(Tenant $tenant, string $termKey, ?string $locale = null): string
    {
        $dict = $this->dictionary($tenant, $locale);
        $entry = $dict[$termKey] ?? null;
        if (is_array($entry)) {
            return (string) ($entry['label'] ?? TerminologyHumanizer::humanize($termKey));
        }

        $term = DomainTerm::query()->where('term_key', $termKey)->first();
        if ($term === null) {
            return TerminologyHumanizer::humanize($termKey);
        }

        if (! $term->is_active) {
            return $this->composeInactiveTermEntry($tenant, $term)['label'];
        }

        return $this->composeTermEntry($term, $this->loadOverride($tenant, $term), $this->loadPresetRow($tenant, $term), $tenant->id)['label'];
    }

    /**
     * Like {@see label()}, but when the term_key is not registered in domain_terms at all,
     * returns $fallback (e.g. resource hardcoded Russian label).
     */
    public function labelWithFallback(Tenant $tenant, string $termKey, string $fallback): string
    {
        if (! DomainTerm::query()->where('term_key', $termKey)->exists()) {
            return $fallback;
        }

        return $this->label($tenant, $termKey);
    }

    public function shortLabel(Tenant $tenant, string $termKey, ?string $locale = null): ?string
    {
        $dict = $this->dictionary($tenant, $locale);
        $entry = $dict[$termKey] ?? null;
        if (is_array($entry)) {
            $short = $entry['short_label'] ?? null;

            return $short !== null && $short !== '' ? (string) $short : null;
        }

        $term = DomainTerm::query()->where('term_key', $termKey)->first();
        if ($term === null) {
            return null;
        }

        if (! $term->is_active) {
            $short = $this->composeInactiveTermEntry($tenant, $term)['short_label'];
        } else {
            $short = $this->composeTermEntry($term, $this->loadOverride($tenant, $term), $this->loadPresetRow($tenant, $term), $tenant->id)['short_label'];
        }

        return $short !== null && $short !== '' ? (string) $short : null;
    }

    /**
     * @param  list<string>  $termKeys
     * @return array<string, string> term_key => label
     */
    public function many(Tenant $tenant, array $termKeys, ?string $locale = null): array
    {
        $dict = $this->dictionary($tenant, $locale);
        $out = [];
        foreach ($termKeys as $key) {
            $entry = $dict[$key] ?? null;
            $out[$key] = is_array($entry)
                ? (string) ($entry['label'] ?? TerminologyHumanizer::humanize($key))
                : $this->label($tenant, $key, $locale);
        }

        return $out;
    }

    /**
     * @return array<string, array{label: string, short_label: ?string, source: string}>
     */
    public function dictionary(Tenant $tenant, ?string $locale = null): array
    {
        $loc = $this->resolveLocale($tenant, $locale);
        $cacheKey = $this->cacheKey($tenant->id, $loc);

        return Cache::rememberForever($cacheKey, function () use ($tenant): array {
            return $this->buildDictionary($tenant);
        });
    }

    public function forgetTenant(int $tenantId): void
    {
        $tenant = Tenant::query()->find($tenantId);
        if ($tenant === null) {
            return;
        }
        Cache::forget($this->cacheKey($tenantId, $this->resolveLocale($tenant, null)));
    }

    public function forgetTenantsUsingPreset(int $presetId): void
    {
        $ids = Tenant::query()
            ->where('domain_localization_preset_id', $presetId)
            ->pluck('id');
        foreach ($ids as $id) {
            $this->forgetTenant((int) $id);
        }
    }

    public function forgetAllTenants(): void
    {
        Tenant::query()
            ->select(['id', 'locale'])
            ->orderBy('id')
            ->chunkById(200, function ($tenants): void {
                foreach ($tenants as $t) {
                    Cache::forget($this->cacheKey((int) $t->id, $this->resolveLocale($t, null)));
                }
            });
    }

    public function cacheKey(int $tenantId, string $locale): string
    {
        return 'terminology.'.self::CACHE_VERSION.'.'.$tenantId.'.'.$locale;
    }

    /**
     * Cache key used by {@see dictionary()} / {@see forgetTenant()} for this tenant (app locale when tenant locale empty).
     */
    public function dictionaryCacheKey(Tenant $tenant): string
    {
        return $this->cacheKey($tenant->id, $this->resolveLocale($tenant, null));
    }

    private function resolveLocale(Tenant $tenant, ?string $locale): string
    {
        $l = $locale ?? $tenant->locale;

        return $l !== null && $l !== '' ? strtolower((string) $l) : strtolower((string) config('app.locale', 'ru'));
    }

    /**
     * @return array<string, array{label: string, short_label: ?string, source: string}>
     */
    private function buildDictionary(Tenant $tenant): array
    {
        $terms = DomainTerm::query()
            ->where('is_active', true)
            ->get()
            ->keyBy('term_key');

        if ($terms->isEmpty()) {
            return [];
        }

        $termIds = $terms->pluck('id')->all();

        $overridesByTermId = TenantTermOverride::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('term_id', $termIds)
            ->get()
            ->keyBy('term_id');

        $presetTermsByTermId = collect();
        if ($tenant->domain_localization_preset_id !== null) {
            $presetTermsByTermId = DomainLocalizationPresetTerm::query()
                ->where('preset_id', $tenant->domain_localization_preset_id)
                ->whereIn('term_id', $termIds)
                ->get()
                ->keyBy('term_id');
        }

        $map = [];
        foreach ($terms as $termKey => $term) {
            /** @var DomainTerm $term */
            $map[$termKey] = $this->composeTermEntry(
                $term,
                $overridesByTermId->get($term->id),
                $presetTermsByTermId->get($term->id),
                $tenant->id
            );
        }

        return $map;
    }

    /**
     * Inactive terms are excluded from the cached dictionary; if addressed directly, ignore preset
     * (term is off) but still honour tenant override and DB default_label.
     *
     * @return array{label: string, short_label: ?string, source: string}
     */
    private function composeInactiveTermEntry(Tenant $tenant, DomainTerm $term): array
    {
        $override = $this->loadOverride($tenant, $term);
        $termKey = $term->term_key;
        $fromDefault = $term->default_label !== null && $term->default_label !== '';
        $systemBase = $fromDefault ? (string) $term->default_label : TerminologyHumanizer::humanize($termKey);

        $label = $override?->label ?? $systemBase;

        $short = $override?->short_label;
        if ($short === '') {
            $short = null;
        }

        if ($override !== null) {
            $source = 'inactive_override';
        } elseif ($fromDefault) {
            $source = 'inactive';
        } else {
            $source = 'inactive_fallback';
            $this->maybeLogTerminologyFallback($termKey, $tenant->id, 'inactive_term_empty_default');
        }

        return [
            'label' => (string) $label,
            'short_label' => $short,
            'source' => $source,
        ];
    }

    /**
     * @return array{label: string, short_label: ?string, source: string}
     */
    private function composeTermEntry(
        DomainTerm $term,
        ?TenantTermOverride $override,
        ?DomainLocalizationPresetTerm $presetRow,
        ?int $logTenantId = null
    ): array {
        $termKey = $term->term_key;
        $fromDefault = $term->default_label !== null && $term->default_label !== '';
        $systemBase = $fromDefault ? (string) $term->default_label : TerminologyHumanizer::humanize($termKey);

        $label = $override?->label ?? $presetRow?->label ?? $systemBase;

        $short = $override?->short_label ?? $presetRow?->short_label;
        if ($short === '') {
            $short = null;
        }

        if ($override !== null) {
            $source = 'override';
        } elseif ($presetRow !== null) {
            $source = 'preset';
        } elseif ($fromDefault) {
            $source = 'default';
        } else {
            $source = 'fallback';
            $this->maybeLogTerminologyFallback($termKey, $logTenantId, 'empty_default_no_preset');
        }

        return [
            'label' => (string) $label,
            'short_label' => $short,
            'source' => $source,
        ];
    }

    private function maybeLogTerminologyFallback(string $termKey, ?int $tenantId, string $reason): void
    {
        if (! config('terminology.log_fallbacks', true)) {
            return;
        }

        if (! config('app.debug') && ! app()->environment('local')) {
            return;
        }

        Log::warning('Terminology fallback label used', [
            'term_key' => $termKey,
            'tenant_id' => $tenantId,
            'reason' => $reason,
        ]);
    }

    private function loadOverride(Tenant $tenant, DomainTerm $term): ?TenantTermOverride
    {
        return TenantTermOverride::query()
            ->where('tenant_id', $tenant->id)
            ->where('term_id', $term->id)
            ->first();
    }

    private function loadPresetRow(Tenant $tenant, DomainTerm $term): ?DomainLocalizationPresetTerm
    {
        if ($tenant->domain_localization_preset_id === null) {
            return null;
        }

        return DomainLocalizationPresetTerm::query()
            ->where('preset_id', $tenant->domain_localization_preset_id)
            ->where('term_id', $term->id)
            ->first();
    }
}
