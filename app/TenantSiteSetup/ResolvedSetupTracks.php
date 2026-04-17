<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

/**
 * Результат {@see SetupTracksResolver}: активные дорожки/слои и отключённые треки с причинами.
 */
final readonly class ResolvedSetupTracks
{
    /**
     * @param  list<string>  $activeTracks
     * @param  list<string>  $activeLayers
     * @param  array<string, string>  $suppressedTracks  track => reason code
     */
    public function __construct(
        public array $activeTracks,
        public array $activeLayers,
        public array $suppressedTracks,
    ) {}
}
