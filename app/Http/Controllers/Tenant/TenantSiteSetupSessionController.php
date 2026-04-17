<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\TenantSiteSetup\SetupSessionService;
use App\TenantSiteSetup\TenantSiteSetupFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * POST-действия полосы быстрого запуска ({@code action}).
 *
 * Продуктовая семантика:
 * - {@code next} → {@see SetupSessionService::advanceToNext} — сдвинуть указатель очереди guided-сессии; **статус пункта
 *   в {@code tenant_setup_item_states} не меняется** (ни «отложено», ни «не требуется»). В UI это «Дальше» или
 *   «Пропустить шаг»: временно идти дальше по маршруту без фиксации решения по пункту.
 * - {@code snooze} — «Позже»: явно помечает пункт и двигает очередь.
 * - {@code not_needed} — «Не требуется»: явное исключение пункта, если разрешено правилами.
 * - {@code pause} — пауза сессии.
 */
final class TenantSiteSetupSessionController extends Controller
{
    public function __invoke(Request $request, SetupSessionService $sessions): RedirectResponse
    {
        abort_unless(TenantSiteSetupFeature::enabled(), 404);
        Gate::authorize('manage_settings');

        $tenant = currentTenant();
        $user = Auth::user();
        abort_if($tenant === null || $user === null, 403);

        $action = (string) $request->input('action', '');
        match ($action) {
            'next' => $sessions->advanceToNext($tenant, $user),
            'snooze' => $sessions->snoozeCurrentAndAdvance($tenant, $user),
            'not_needed' => $sessions->markNotNeededCurrentAndAdvance($tenant, $user),
            'pause' => $sessions->pause($tenant, $user),
            'start_fresh' => $sessions->startFreshGuidedSession($tenant, $user),
            default => abort(400, 'Неизвестное действие мастера.'),
        };

        return redirect()->back();
    }
}
