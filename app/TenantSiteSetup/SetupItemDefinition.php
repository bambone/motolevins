<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

final readonly class SetupItemDefinition
{
    /**
     * @param  list<string>  $prerequisiteKeys
     * @param  list<string>  $profileDependencyKeys
     * @param  list<string>  $completionRefreshTags
     * @param  list<string>|null  $themeConstraints  restrict to these theme_key values; null = any
     * @param  list<string>|null  $targetFallbackKeys  extra {@see data-setup-target} keys to try (first visible wins)
     * @param  list<string>|null  $pageBuilderFallbackSectionTypeIds  catalog tile {@see data-setup-section-type} ids when target DOM is missing
     * @param  string|null  $fallbackSetupAction  {@see data-setup-action} when section tiles are not enough (e.g. open «Блок»)
     */
    public function __construct(
        public string $key,
        public string $categoryKey,
        public string $title,
        public string $description,
        public SetupItemImportance $importance,
        public int $sortOrder,
        public ?string $filamentRouteName,
        public SetupItemTargetKind $targetKind,
        public string $targetKey,
        public string $targetLabel,
        public array $prerequisiteKeys,
        public bool $skipAllowed,
        public bool $notNeededAllowed,
        public bool $launchCritical,
        public array $profileDependencyKeys,
        public array $completionRefreshTags,
        public ?array $themeConstraints,
        public ?array $featureConstraints,
        public ?array $targetFallbackKeys = null,
        public ?array $pageBuilderFallbackSectionTypeIds = null,
        public ?string $fallbackSetupAction = null,
    ) {}
}
