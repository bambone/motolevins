<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\TenantSiteSetup\SetupItemRegistry;
use App\TenantSiteSetup\SetupItemStateService;
use App\TenantSiteSetup\TenantSiteSetupFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class TenantSiteSetupRestoreItemController extends Controller
{
    public function __invoke(Request $request, SetupItemStateService $itemStates): RedirectResponse
    {
        abort_unless(TenantSiteSetupFeature::enabled(), 404);
        Gate::authorize('manage_settings');

        $tenant = currentTenant();
        $user = Auth::user();
        abort_if($tenant === null || $user === null, 403);

        $validKeys = array_keys(SetupItemRegistry::definitions());
        $validated = $request->validate([
            'item_key' => ['required', 'string', Rule::in($validKeys)],
        ]);

        $itemStates->restoreToPending($tenant, $user, $validated['item_key']);

        return redirect()->back();
    }
}
