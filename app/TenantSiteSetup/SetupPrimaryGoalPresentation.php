<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

/**
 * Данные цели сайта для UI (label, hint, приоритетные дорожки).
 */
final readonly class SetupPrimaryGoalPresentation
{
    /**
     * @param  array<string, bool>  $recommendedTracks  keyed by SetupOnboardingTrack::value
     */
    public function __construct(
        public string $code,
        public string $label,
        public string $hint,
        public array $recommendedTracks,
    ) {}
}
