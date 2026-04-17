<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

use App\Models\Tenant;
use App\Models\TenantSetupItemState;

final class SetupJourneyBuilder
{
    public function __construct(
        private readonly SetupApplicabilityEvaluator $applicability,
        private readonly SetupCompletionEvaluator $completion,
    ) {}

    /**
     * Ordered item keys for guided mode (skip not applicable; skip completed unless forced).
     *
     * @return list<string>
     */
    public function visibleStepKeys(Tenant $tenant): array
    {
        $definitions = collect(SetupItemRegistry::definitions())
            ->sortBy(fn (SetupItemDefinition $d) => $d->sortOrder);

        $keys = [];
        foreach ($definitions as $key => $def) {
            if ($this->applicability->evaluateItem($tenant, $def) !== 'applicable') {
                continue;
            }
            $state = TenantSetupItemState::query()
                ->where('tenant_id', $tenant->id)
                ->where('item_key', $key)
                ->value('current_status');
            if ($state === 'snoozed') {
                continue;
            }
            if ($state === 'not_needed') {
                continue;
            }
            if ($this->completion->isComplete($tenant, $def) || $state === 'completed') {
                continue;
            }
            $keys[] = $key;
        }

        return $keys;
    }
}
