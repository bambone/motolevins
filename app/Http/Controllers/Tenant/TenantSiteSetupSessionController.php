<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Filament\Tenant\Pages\TenantSiteSetupCenterPage;
use App\Http\Controllers\Controller;
use App\TenantSiteSetup\SetupProgressService;
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
    public function __invoke(Request $request, SetupSessionService $sessions, SetupProgressService $progress): RedirectResponse
    {
        abort_unless(TenantSiteSetupFeature::enabled(), 404);
        Gate::authorize('manage_settings');

        $tenant = currentTenant();
        $user = Auth::user();
        abort_if($tenant === null || $user === null, 403);

        $action = (string) $request->input('action', '');

        if ($action === 'next') {
            if ($sessions->advanceToNext($tenant, $user)) {
                $summary = $progress->computeSummary($tenant);
                $qA = (int) ($summary['quick_launch_applicable'] ?? 0);
                $qC = (int) ($summary['quick_launch_completed'] ?? 0);
                $variant = ($qA > 0 && $qC >= $qA) ? 'base_launch' : 'checklist';

                return redirect()->to(TenantSiteSetupCenterPage::getUrl())
                    ->with('site_setup_guided_completed', $variant);
            }

            return redirect()->back();
        }

        match ($action) {
            'snooze' => $sessions->snoozeCurrentAndAdvance($tenant, $user),
            'not_needed' => $sessions->markNotNeededCurrentAndAdvance($tenant, $user),
            'pause' => $sessions->pause($tenant, $user),
            'start_fresh' => $sessions->startFreshGuidedSession($tenant, $user),
            default => abort(400, 'Неизвестное действие мастера.'),
        };

        return redirect()->back();
    }
}
