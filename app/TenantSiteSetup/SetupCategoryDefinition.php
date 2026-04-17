<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

final readonly class SetupCategoryDefinition
{
    public function __construct(
        public string $key,
        public string $title,
        public ?string $description,
        public int $sortOrder,
        public int $launchPhase,
        public bool $defaultExpanded,
        public ?string $icon,
    ) {}
}
