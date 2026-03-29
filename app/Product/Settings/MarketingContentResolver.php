<?php

namespace App\Product\Settings;

use App\Models\PlatformSetting;

/**
 * Фаза 1: маркетинговый контент платформы — merge config/platform_marketing и overlay из БД.
 *
 * Целевое end-state (общий слой): context-aware {@see SettingsContext} / ResolvedSettings для platform и tenant,
 * единая таксономия ключей; этот класс остаётся специализацией для лендинга платформы или делегирует в общий resolver.
 */
final class MarketingContentResolver
{
    /**
     * @return array<string, mixed>
     */
    public function resolved(): array
    {
        $base = config('platform_marketing', []);
        if (! is_array($base)) {
            $base = [];
        }

        $overlay = PlatformSetting::get('marketing.config_overlay', []);
        if (! is_array($overlay)) {
            $overlay = [];
        }

        return $this->mergeRecursive($base, $overlay);
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $overlay
     * @return array<string, mixed>
     */
    private function mergeRecursive(array $base, array $overlay): array
    {
        foreach ($overlay as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                /** @var array<string, mixed> $nestedBase */
                $nestedBase = $base[$key];
                $base[$key] = $this->mergeRecursive($nestedBase, $value);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }
}
