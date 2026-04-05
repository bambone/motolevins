<?php

namespace App\Services\Seo;

use App\Models\PlatformSetting;

/**
 * Fills marketing.seo.llms_* from generator when empty (platform console).
 */
final class InitializePlatformMarketingSeoDefaults
{
    public function __construct(
        private PlatformMarketingLlmsGenerator $generator,
    ) {}

    /**
     * @return list<string>
     */
    public function execute(bool $force = false): array
    {
        $messages = [];
        $built = $this->generator->generate();

        $introCurrent = trim((string) PlatformSetting::get('marketing.seo.llms_intro', ''));
        if ($force || $introCurrent === '') {
            PlatformSetting::set('marketing.seo.llms_intro', $built['intro'], 'string');
            $messages[] = 'marketing.seo.llms_intro set';
        }

        $entriesCurrent = trim((string) PlatformSetting::get('marketing.seo.llms_entries', ''));
        if ($force || $entriesCurrent === '') {
            $json = json_encode($built['entries'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($json !== false) {
                PlatformSetting::set('marketing.seo.llms_entries', $json, 'string');
                $messages[] = 'marketing.seo.llms_entries set';
            }
        }

        return $messages;
    }
}
