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
