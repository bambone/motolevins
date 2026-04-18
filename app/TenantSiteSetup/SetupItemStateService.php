<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

use App\Models\Tenant;
use App\Models\TenantSetupItemState;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class SetupItemStateService
{
    public function findState(int $tenantId, string $itemKey): ?TenantSetupItemState
    {
        return TenantSetupItemState::query()
            ->where('tenant_id', $tenantId)
            ->where('item_key', $itemKey)
            ->first();
    }

    /**
     * @param  array<string, mixed>|null  $completionResultJson
     */
    public function markCompletedBySystem(
        Tenant $tenant,
        string $itemKey,
        string $categoryKey,
        ?array $snapshot,
        ?string $routeName,
    ): void {
        TenantSetupItemState::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'item_key' => $itemKey],
            [
                'category_key' => $categoryKey,
                'current_status' => 'completed',
                'applicability_status' => 'applicable',
                'completed_at' => now(),
                'completed_value_json' => $snapshot,
                'completion_source' => 'auto',
                'last_evaluated_at' => now(),
                'last_completion_check_at' => now(),
                'last_completion_result_json' => ['ok' => true],
                'last_target_route_name' => $routeName,
            ],
        );
        SetupProgressCache::forget((int) $tenant->id);
    }

    public function markSnoozed(
        Tenant $tenant,
        User $user,
        string $itemKey,
        string $reasonCode,
        ?string $comment,
    ): void {
        $this->assertManageSettings();
        $def = $this->getDefOrFail($itemKey);
        if (! $def->skipAllowed) {
            abort(422, 'Шаг нельзя отложить.');
        }

        TenantSetupItemState::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'item_key' => $itemKey],
            [
                'category_key' => $def->categoryKey,
                'current_status' => 'snoozed',
                'applicability_status' => 'applicable',
                'snooze_reason_code' => $reasonCode,
                'reason_comment' => $comment,
                'updated_by_user_id' => $user->id,
            ],
        );
        SetupProgressCache::forget((int) $tenant->id);
    }

    public function markNotNeeded(
        Tenant $tenant,
        User $user,
        string $itemKey,
        string $reasonCode,
        ?string $comment,
    ): void {
        $this->assertManageSettings();
        $def = $this->getDefOrFail($itemKey);
        if (! $def->notNeededAllowed) {
            abort(422, 'Шаг нельзя пометить как «не требуется».');
        }

        TenantSetupItemState::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'item_key' => $itemKey],
            [
                'category_key' => $def->categoryKey,
                'current_status' => 'not_needed',
                'applicability_status' => 'applicable',
                'not_needed_reason_code' => $reasonCode,
                'reason_comment' => $comment,
                'updated_by_user_id' => $user->id,
            ],
        );
        SetupProgressCache::forget((int) $tenant->id);
    }

    /**
     * Если данные перестали удовлетворять completion rule, снимаем автоматическое «выполнено».
     */
    public function demoteCompletedWhenDataRegressed(Tenant $tenant, SetupItemDefinition $def, bool $dataComplete): void
    {
        if ($dataComplete) {
            return;
        }

        $state = $this->findState((int) $tenant->id, $def->key);
        if ($state === null || $state->current_status !== 'completed') {
            return;
        }

        TenantSetupItemState::query()
            ->where('tenant_id', $tenant->id)
            ->where('item_key', $def->key)
            ->update([
                'current_status' => 'pending',
                'completed_at' => null,
                'completed_value_json' => null,
                'completion_source' => null,
                'last_completion_result_json' => ['ok' => false, 'reason' => 'data_regressed'],
                'last_evaluated_at' => now(),
            ]);
        SetupProgressCache::forget((int) $tenant->id);
    }

    public function restoreToPending(Tenant $tenant, User $user, string $itemKey): void
    {
        $this->assertManageSettings();
        $def = $this->getDefOrFail($itemKey);

        TenantSetupItemState::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'item_key' => $itemKey],
            [
                'category_key' => $def->categoryKey,
                'current_status' => 'pending',
                'applicability_status' => 'applicable',
                'snooze_reason_code' => null,
                'not_needed_reason_code' => null,
                'reason_comment' => null,
                'completed_at' => null,
                'updated_by_user_id' => $user->id,
            ],
        );
        SetupProgressCache::forget((int) $tenant->id);
    }

    private function getDefOrFail(string $itemKey): SetupItemDefinition
    {
        $map = SetupItemRegistry::definitions();
        if (! isset($map[$itemKey])) {
            abort(404);
        }

        return $map[$itemKey];
    }

    private function assertManageSettings(): void
    {
        Gate::authorize('manage_settings');
    }
}
