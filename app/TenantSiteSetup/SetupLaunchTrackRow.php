<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

/**
 * Одна строка дорожки для Blade / виджета.
 */
final readonly class SetupLaunchTrackRow
{
    /**
     * @param  array<string, mixed>  $primaryGoal
     */
    public function __construct(
        public string $key,
        public string $label,
        public SetupLaunchUiTrackState $state,
        public ?string $reasonCode,
        public string $reasonTitle,
        public string $reasonBody,
        public ?string $actionHint,
        public int $itemsTotal,
        public int $itemsApplicable,
        public int $itemsNotApplicableBySystem,
        public int $itemsCompleted,
        public bool $recommended,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'state' => $this->state->value,
            'reason_code' => $this->reasonCode,
            'reason_title' => $this->reasonTitle,
            'reason_body' => $this->reasonBody,
            'action_hint' => $this->actionHint,
            'items_total' => $this->itemsTotal,
            'items_applicable' => $this->itemsApplicable,
            'items_not_applicable_by_system' => $this->itemsNotApplicableBySystem,
            'items_completed' => $this->itemsCompleted,
            'recommended' => $this->recommended,
        ];
    }
}
