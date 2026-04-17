<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

use App\Models\Tenant;

final class SetupApplicabilityEvaluator
{
    public function __construct(
        private readonly SetupProfileRepository $profiles,
    ) {}

    /**
     * Returns applicability_status string (see blueprint §1.2).
     */
    public function evaluateItem(Tenant $tenant, SetupItemDefinition $def): string
    {
        if ($def->themeConstraints !== null && $def->themeConstraints !== []) {
            if (! in_array((string) $tenant->theme_key, $def->themeConstraints, true)) {
                return 'not_applicable_by_system';
            }
        }

        if ($def->featureConstraints !== null) {
            foreach ($def->featureConstraints as $flag => $expected) {
                if ($flag === 'scheduling_module_enabled' && (bool) $tenant->scheduling_module_enabled !== (bool) $expected) {
                    return 'not_applicable_by_system';
                }
            }
        }

        $profile = $this->profiles->get((int) $tenant->id);
        foreach ($def->profileDependencyKeys as $pkey) {
            if (array_key_exists($pkey, $profile)) {
                // Reserved: profile can force not_applicable for optional modules later.
            }
        }

        return 'applicable';
    }
}
