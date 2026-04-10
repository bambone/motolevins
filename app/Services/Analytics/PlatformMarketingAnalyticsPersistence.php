<?php

namespace App\Services\Analytics;

use App\Models\PlatformSetting;
use App\Support\Analytics\AnalyticsSettingsData;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;

/**
 * Analytics for the central marketing site (tenancy.central_domains), same JSON shape as tenant {@see AnalyticsSettingsPersistence}.
 */
final class PlatformMarketingAnalyticsPersistence
{
    public const SETTING_KEY = 'marketing.integrations.analytics';

    public function load(): AnalyticsSettingsData
    {
        $raw = PlatformSetting::get(self::SETTING_KEY, null);

        return AnalyticsSettingsData::fromStorage(is_array($raw) ? $raw : null);
    }

    public function save(
        AnalyticsSettingsData $data,
        ?Authenticatable $actor,
        ?AnalyticsSettingsData $before = null,
    ): void {
        $before ??= $this->load();

        PlatformSetting::set(self::SETTING_KEY, $data->toStorageArray(), 'json');

        if ($before->equals($data)) {
            return;
        }

        Log::info('platform_marketing_analytics_settings_updated', [
            'actor_id' => $actor?->getAuthIdentifier(),
            'before' => $before->sanitizedForLog(),
            'after' => $data->sanitizedForLog(),
        ]);
    }
}
