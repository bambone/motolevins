<?php

namespace App\Http\Controllers;

use App\Http\Requests\TenantBookingCatalogAvailabilityRequest;
use App\Http\Requests\TenantBookingMotorcycleCalendarHintsRequest;
use App\Services\TenantPublicBookingAvailabilityService;
use Illuminate\Http\JsonResponse;

class TenantPublicBookingAvailabilityController extends Controller
{
    public function catalogAvailability(
        TenantBookingCatalogAvailabilityRequest $request,
        TenantPublicBookingAvailabilityService $service,
    ): JsonResponse {
        $tenant = currentTenant();
        abort_if($tenant === null, 404);

        $v = $request->validated();
        $map = $service->catalogAvailabilityForMotorcycles(
            (int) $tenant->id,
            $v['motorcycle_ids'],
            $v['start_date'],
            $v['end_date'],
        );

        $availability = [];
        foreach ($map as $id => $available) {
            $availability[(string) $id] = $available;
        }

        return response()->json(['availability' => $availability]);
    }

    public function motorcycleCalendarHints(
        TenantBookingMotorcycleCalendarHintsRequest $request,
        TenantPublicBookingAvailabilityService $service,
    ): JsonResponse {
        $tenant = currentTenant();
        abort_if($tenant === null, 404);

        $v = $request->validated();
        $payload = $service->motorcycleCalendarHints(
            (int) $tenant->id,
            (int) $v['motorcycle_id'],
            $v['range_from'],
            $v['range_to'],
            $v['selected_start'] ?? null,
            $v['selected_end'] ?? null,
            $v['phone'] ?? null,
        );

        return response()->json($payload);
    }
}
