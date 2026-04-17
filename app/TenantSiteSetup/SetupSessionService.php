<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

use App\Models\Tenant;
use App\Models\TenantSetupSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class SetupSessionService
{
    public function __construct(
        private readonly JourneyVersion $journeyVersion,
        private readonly SetupProfileRepository $profiles,
        private readonly SetupJourneyBuilder $journeyBuilder,
        private readonly SetupItemStateService $itemStates,
        private readonly PageBuilderSetupTargetResolver $pageBuilderHints,
    ) {}

    public function startOrResume(Tenant $tenant, User $user): TenantSetupSession
    {
        Gate::authorize('manage_settings');
        $paused = $this->pausedSession($tenant, $user);
        if ($paused !== null) {
            return $this->resumePausedSession($tenant, $user, $paused);
        }

        return $this->createNewSession($tenant, $user);
    }

    /**
     * Abandon active/paused guided sessions and start a new queue from scratch.
     */
    public function startFreshGuidedSession(Tenant $tenant, User $user): TenantSetupSession
    {
        Gate::authorize('manage_settings');
        DB::transaction(function () use ($tenant, $user): void {
            TenantSetupSession::query()
                ->where('tenant_id', $tenant->id)
                ->where('user_id', $user->id)
                ->whereIn('session_status', ['active', 'paused'])
                ->update(['session_status' => 'abandoned']);
        });

        return $this->createNewSession($tenant, $user);
    }

    public function pausedSession(Tenant $tenant, User $user): ?TenantSetupSession
    {
        return TenantSetupSession::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->where('session_status', 'paused')
            ->orderByDesc('id')
            ->first();
    }

    private function createNewSession(Tenant $tenant, User $user): TenantSetupSession
    {
        $version = $this->journeyVersion->compute($tenant, $this->profiles);
        $keys = $this->journeyBuilder->visibleStepKeys($tenant);

        return DB::transaction(function () use ($tenant, $user, $version, $keys): TenantSetupSession {
            TenantSetupSession::query()
                ->where('tenant_id', $tenant->id)
                ->where('user_id', $user->id)
                ->whereIn('session_status', ['active', 'paused'])
                ->update(['session_status' => 'abandoned']);

            $first = $keys[0] ?? null;
            $def = $first !== null ? SetupItemRegistry::definitions()[$first] ?? null : null;

            return TenantSetupSession::query()->create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'session_status' => 'active',
                'current_item_key' => $first,
                'current_route_name' => $def?->filamentRouteName,
                'journey_version' => $version,
                'step_index' => 0,
                'visible_step_keys_json' => $keys,
                'meta_json' => [],
                'started_at' => now(),
            ]);
        });
    }

    private function resumePausedSession(Tenant $tenant, User $user, TenantSetupSession $paused): TenantSetupSession
    {
        $version = $this->journeyVersion->compute($tenant, $this->profiles);
        $keys = $this->journeyBuilder->visibleStepKeys($tenant);

        return DB::transaction(function () use ($tenant, $user, $paused, $version, $keys): TenantSetupSession {
            TenantSetupSession::query()
                ->where('tenant_id', $tenant->id)
                ->where('user_id', $user->id)
                ->where('session_status', 'active')
                ->where('id', '!=', $paused->id)
                ->update(['session_status' => 'abandoned']);

            $paused->update([
                'session_status' => 'active',
                'paused_at' => null,
                'journey_version' => $version,
                'visible_step_keys_json' => $keys,
            ]);
            $paused->refresh();
            $this->realignCurrentPointerAfterResume($paused, $keys);

            return $paused->fresh();
        });
    }

    /**
     * @param  list<string>  $keys
     */
    private function realignCurrentPointerAfterResume(TenantSetupSession $session, array $keys): void
    {
        if ($keys === []) {
            $this->completeSession($session);

            return;
        }

        $defs = SetupItemRegistry::definitions();
        $current = $session->current_item_key;
        if ($current !== null && in_array($current, $keys, true)) {
            $idx = array_search($current, $keys, true);
            $def = $defs[$current] ?? null;
            $session->update([
                'step_index' => $idx === false ? 0 : (int) $idx,
                'current_route_name' => $def?->filamentRouteName,
            ]);

            return;
        }

        $first = $keys[0];
        $def = $defs[$first] ?? null;
        $session->update([
            'current_item_key' => $first,
            'step_index' => 0,
            'current_route_name' => $def?->filamentRouteName,
        ]);
    }

    public function pause(Tenant $tenant, User $user): void
    {
        Gate::authorize('manage_settings');
        TenantSetupSession::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->where('session_status', 'active')
            ->update([
                'session_status' => 'paused',
                'paused_at' => now(),
            ]);
    }

    public function advanceToNext(Tenant $tenant, User $user): void
    {
        Gate::authorize('manage_settings');
        $session = $this->requireActiveSession($tenant, $user);
        $defs = SetupItemRegistry::definitions();
        $keys = $this->journeyBuilder->visibleStepKeys($tenant);
        $session->update(['visible_step_keys_json' => $keys]);

        $current = $session->current_item_key;
        $idx = $current !== null ? array_search($current, $keys, true) : false;
        if ($idx === false) {
            $nextKey = $keys[0] ?? null;
            $nextIdx = 0;
        } else {
            $nextKey = $keys[(int) $idx + 1] ?? null;
            $nextIdx = (int) $idx + 1;
        }

        if ($nextKey === null) {
            $this->completeSession($session);

            return;
        }

        $nextDef = $defs[$nextKey] ?? null;
        $session->update([
            'current_item_key' => $nextKey,
            'step_index' => $nextIdx,
            'current_route_name' => $nextDef?->filamentRouteName,
        ]);
    }

    public function snoozeCurrentAndAdvance(Tenant $tenant, User $user): void
    {
        Gate::authorize('manage_settings');
        $session = $this->requireActiveSession($tenant, $user);
        $currentKey = $session->current_item_key;
        if ($currentKey === null) {
            abort(422, 'Нет текущего шага.');
        }

        $this->itemStates->markSnoozed($tenant, $user, $currentKey, 'guided_snooze', null);
        $this->repositionAfterUserChoice($tenant, $session, $currentKey);
    }

    public function markNotNeededCurrentAndAdvance(Tenant $tenant, User $user): void
    {
        Gate::authorize('manage_settings');
        $session = $this->requireActiveSession($tenant, $user);
        $currentKey = $session->current_item_key;
        if ($currentKey === null) {
            abort(422, 'Нет текущего шага.');
        }

        $this->itemStates->markNotNeeded($tenant, $user, $currentKey, 'guided_not_needed', null);
        $this->repositionAfterUserChoice($tenant, $session, $currentKey);
    }

    /**
     * @param  list<string>  $keys
     */
    private function repositionAfterUserChoice(Tenant $tenant, TenantSetupSession $session, string $previousKey): void
    {
        $defs = SetupItemRegistry::definitions();
        $keys = $this->journeyBuilder->visibleStepKeys($tenant);
        $session->update(['visible_step_keys_json' => $keys]);

        if ($keys === []) {
            $this->completeSession($session);

            return;
        }

        $nextKey = $this->pickNextGuidedKey($previousKey, $keys);
        if ($nextKey === null) {
            $this->completeSession($session);

            return;
        }

        $idx = array_search($nextKey, $keys, true);
        $def = $defs[$nextKey] ?? null;
        $session->update([
            'current_item_key' => $nextKey,
            'step_index' => $idx === false ? 0 : (int) $idx,
            'current_route_name' => $def?->filamentRouteName,
        ]);
    }

    /**
     * @param  list<string>  $keys
     */
    private function pickNextGuidedKey(string $previousKey, array $keys): ?string
    {
        if ($keys === []) {
            return null;
        }

        $defs = SetupItemRegistry::definitions();
        $prevOrder = $defs[$previousKey]->sortOrder ?? -1;

        $withHigher = array_values(array_filter(
            $keys,
            fn (string $k): bool => ($defs[$k]->sortOrder ?? 999) > $prevOrder,
        ));
        usort(
            $withHigher,
            fn (string $a, string $b): int => ($defs[$a]->sortOrder ?? 0) <=> ($defs[$b]->sortOrder ?? 0),
        );
        if ($withHigher !== []) {
            return $withHigher[0];
        }

        $sorted = $keys;
        usort(
            $sorted,
            fn (string $a, string $b): int => ($defs[$a]->sortOrder ?? 0) <=> ($defs[$b]->sortOrder ?? 0),
        );

        return $sorted[0] ?? null;
    }

    private function requireActiveSession(Tenant $tenant, User $user): TenantSetupSession
    {
        $session = $this->activeSession($tenant, $user);
        if ($session === null) {
            abort(404, 'Активная сессия мастера не найдена.');
        }

        return $session;
    }

    private function completeSession(TenantSetupSession $session): void
    {
        $session->update([
            'session_status' => 'completed',
            'completed_at' => now(),
            'current_item_key' => null,
            'current_route_name' => null,
        ]);
    }

    public function activeSession(Tenant $tenant, User $user): ?TenantSetupSession
    {
        return TenantSetupSession::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->where('session_status', 'active')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function overlayPayload(?Tenant $tenant, ?User $user): ?array
    {
        if (! TenantSiteSetupFeature::enabled() || $tenant === null || $user === null) {
            return null;
        }

        $session = $this->activeSession($tenant, $user);
        if ($session === null) {
            return null;
        }

        $version = $this->journeyVersion->compute($tenant, $this->profiles);
        if ($session->journey_version !== $version) {
            $session->update([
                'journey_version' => $version,
                'visible_step_keys_json' => $this->journeyBuilder->visibleStepKeys($tenant),
            ]);
            $session->refresh();
        }

        $keys = $session->visible_step_keys_json ?? [];
        $currentKey = $session->current_item_key;
        $defs = SetupItemRegistry::definitions();
        $def = $currentKey !== null && isset($defs[$currentKey]) ? $defs[$currentKey] : null;
        $hints = $def !== null ? $this->pageBuilderHints->overlayHints($def) : [
            'target_fallback_keys' => [],
            'page_builder_fallback_section_types' => [],
            'fallback_setup_action' => null,
        ];

        $sessionUrl = route('filament.admin.tenant-site-setup.session');

        return [
            'session_id' => $session->id,
            'current_item_key' => $currentKey,
            'current_title' => $def?->title,
            'step_index' => (int) $session->step_index,
            'steps_total' => max(1, count($keys)),
            'target_key' => $def?->targetKey,
            'target_fallback_keys' => $hints['target_fallback_keys'],
            'page_builder_fallback_section_types' => $hints['page_builder_fallback_section_types'],
            'fallback_setup_action' => $hints['fallback_setup_action'],
            'route_name' => $def?->filamentRouteName,
            'session_action_url' => $sessionUrl,
            'can_snooze' => $def?->skipAllowed ?? false,
            'can_not_needed' => (bool) ($def?->notNeededAllowed),
            'launch_critical' => $def?->launchCritical ?? false,
        ];
    }
}
