<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\NotificationPushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantNotificationPushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $tenant = currentTenant();
        if ($tenant === null) {
            return response()->json(['message' => 'Tenant context missing'], 404);
        }

        $user = Auth::user();
        if ($user === null) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:2048'],
            'public_key' => ['required', 'string', 'max:255'],
            'auth_token' => ['required', 'string', 'max:255'],
            'device_label' => ['nullable', 'string', 'max:255'],
        ]);

        $endpointHash = hash('sha256', $validated['endpoint']);

        NotificationPushSubscription::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'endpoint_hash' => $endpointHash,
            ],
            [
                'endpoint' => $validated['endpoint'],
                'public_key' => $validated['public_key'],
                'auth_token' => $validated['auth_token'],
                'user_agent' => substr((string) $request->userAgent(), 0, 2000),
                'device_label' => $validated['device_label'] ?? null,
                'last_seen_at' => now(),
                'is_active' => true,
            ],
        );

        return response()->json(['success' => true]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $tenant = currentTenant();
        $user = Auth::user();
        if ($tenant === null || $user === null) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $endpoint = $request->input('endpoint');
        if (! is_string($endpoint) || $endpoint === '') {
            return response()->json(['message' => 'endpoint required'], 422);
        }

        $endpointHash = hash('sha256', $endpoint);

        NotificationPushSubscription::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->where('endpoint_hash', $endpointHash)
            ->update(['is_active' => false]);

        return response()->json(['success' => true]);
    }
}
