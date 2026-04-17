<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

/**
 * Агрегат по пунктам реестра для одной дорожки {@see SetupOnboardingTrack}.
 *
 * `itemsApplicable` — пункты в контуре чеклиста (как знаменатель {@see SetupProgressService}).
 */
final readonly class SetupTrackApplicabilityMetrics
{
    public function __construct(
        public int $itemsTotal,
        public int $itemsApplicable,
        public int $itemsNotApplicableBySystem,
        public int $itemsCompleted,
    ) {}
}
