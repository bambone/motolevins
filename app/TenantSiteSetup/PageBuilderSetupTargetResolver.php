<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

/**
 * Extension point for page-builder guided hints (catalog tiles, add-block actions).
 * Values mirror {@see SetupItemDefinition} overlay fields; centralize if logic grows.
 */
final class PageBuilderSetupTargetResolver
{
    /**
     * @return array{
     *   target_fallback_keys: list<string>,
     *   page_builder_fallback_section_types: list<string>,
     *   fallback_setup_action: ?string,
     * }
     */
    public function overlayHints(SetupItemDefinition $def): array
    {
        return [
            'target_fallback_keys' => $def->targetFallbackKeys ?? [],
            'page_builder_fallback_section_types' => $def->pageBuilderFallbackSectionTypeIds ?? [],
            'fallback_setup_action' => $def->fallbackSetupAction,
        ];
    }
}
