<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

/**
 * Единый payload для обзора запуска и виджета.
 */
final readonly class SetupLaunchContext
{
    /**
     * @param  list<SetupLaunchTrackRow>  $tracks
     * @param  list<string>  $layers
     */
    public function __construct(
        public SetupPrimaryGoalPresentation $primaryGoal,
        public array $tracks,
        public array $layers,
        public int $suppressedCount,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'primary_goal' => [
                'code' => $this->primaryGoal->code,
                'label' => $this->primaryGoal->label,
                'hint' => $this->primaryGoal->hint,
                'recommended_tracks' => $this->primaryGoal->recommendedTracks,
            ],
            'tracks' => array_map(static fn (SetupLaunchTrackRow $r) => $r->toArray(), $this->tracks),
            'layers' => $this->layers,
            'suppressed_count' => $this->suppressedCount,
        ];
    }
}
