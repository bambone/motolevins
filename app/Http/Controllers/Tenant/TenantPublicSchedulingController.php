<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AppointmentHold;
use App\Models\BookableService;
use App\Scheduling\AppointmentInboundService;
use App\Scheduling\Enums\SchedulingScope;
use App\Scheduling\Enums\SchedulingTargetType;
use App\Scheduling\Occupancy\RentalAvailabilityBridge;
use App\Scheduling\RentalUnitSchedulingLabel;
use App\Scheduling\SchedulingEntitlementService;
use App\Scheduling\SchedulingIntegrationGate;
use App\Scheduling\SchedulingStaleBusyEvaluator;
use App\Scheduling\SlotEngineService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TenantPublicSchedulingController extends Controller
{
    private function serializeBookableServiceForPublicList(BookableService $service): array
    {
        $m = $service->resolvedMotorcycle();
        $ru = $service->rentalUnit;

        return [
            'id' => $service->id,
            'slug' => $service->slug,
            'title' => $service->title,
            'description' => $service->description,
            'duration_minutes' => $service->duration_minutes,
            'requires_confirmation' => $service->requires_confirmation,
            'link_type' => $service->linkType()->value,
            'motorcycle_id' => $service->motorcycle_id,
            'motorcycle_slug' => $m?->slug,
            'motorcycle_name' => $m?->name,
            'rental_unit_id' => $service->rental_unit_id,
            'rental_unit_label' => $ru !== null ? RentalUnitSchedulingLabel::label($ru) : null,
        ];
    }

    public function bookableServices(Request $request, SchedulingEntitlementService $entitlements): JsonResponse
    {
        $tenant = currentTenant();
        abort_if($tenant === null, 404);
        if (! $entitlements->tenantCanUseScheduling($tenant)) {
            return response()->json(['services' => []]);
        }

        $services = BookableService::query()
            ->where('bookable_services.scheduling_scope', SchedulingScope::Tenant)
            ->where('bookable_services.tenant_id', $tenant->id)
            ->where('bookable_services.is_active', true)
            ->join('scheduling_targets as st', function ($join): void {
                $join->on('st.target_id', '=', 'bookable_services.id')
                    ->where('st.target_type', SchedulingTargetType::BookableService->value)
                    ->whereColumn('st.scheduling_scope', 'bookable_services.scheduling_scope')
                    ->whereColumn('st.tenant_id', 'bookable_services.tenant_id')
                    ->where('st.scheduling_enabled', true)
                    ->where('st.is_active', true);
            })
            ->select('bookable_services.*')
            ->with(['motorcycle', 'rentalUnit.motorcycle'])
            ->orderBy('bookable_services.sort_weight')
            ->orderBy('bookable_services.title')
            ->get();

        return response()->json([
            'services' => $services->map(fn (BookableService $s): array => $this->serializeBookableServiceForPublicList($s))->values()->all(),
        ]);
    }

    public function slots(
        Request $request,
        int $id,
        SlotEngineService $slotEngine,
        SchedulingEntitlementService $entitlements,
        SchedulingIntegrationGate $integrationGate,
        SchedulingStaleBusyEvaluator $staleBusyEvaluator,
        RentalAvailabilityBridge $rentalAvailabilityBridge,
    ): JsonResponse {
        $tenant = currentTenant();
        abort_if($tenant === null, 404);
        $bookableService = BookableService::query()
            ->where('scheduling_scope', SchedulingScope::Tenant)
            ->where('tenant_id', $tenant->id)
            ->whereKey($id)
            ->firstOrFail();

        if (! $entitlements->tenantCanUseScheduling($tenant)) {
            abort(404);
        }

        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        $from = Carbon::parse($validated['from'], 'UTC')->startOfDay();
        $to = Carbon::parse($validated['to'], 'UTC')->endOfDay();

        $slots = $slotEngine->slotsForBookableService($bookableService, $from, $to);

        $warnings = $integrationGate->warningCodesForTenant($tenant);
        $target = $bookableService->schedulingTarget;
        if ($target !== null
            && $rentalAvailabilityBridge->staleDataWarnsOnly($target, $tenant)
            && $staleBusyEvaluator->subscriptionsAreStaleForTarget($target)) {
            $warnings[] = 'scheduling_external_busy_stale';
        }

        return response()->json([
            'slots' => $slots,
            'warnings' => array_values(array_unique($warnings)),
        ]);
    }

    public function hold(Request $request, AppointmentInboundService $inbound, SchedulingEntitlementService $entitlements): JsonResponse
    {
        $tenant = currentTenant();
        abort_if($tenant === null, 404);
        if (! $entitlements->tenantCanUseScheduling($tenant)) {
            abort(404);
        }

        $data = $request->validate([
            'bookable_service_id' => ['required', 'integer'],
            'scheduling_resource_id' => ['required', 'integer'],
            'starts_at_utc' => ['required', 'date'],
            'ends_at_utc' => ['required', 'date', 'after:starts_at_utc'],
        ]);

        $service = BookableService::query()
            ->where('scheduling_scope', SchedulingScope::Tenant)
            ->where('tenant_id', $tenant->id)
            ->whereKey((int) $data['bookable_service_id'])
            ->firstOrFail();

        try {
            $hold = $inbound->createHold(
                $tenant,
                $service,
                (int) $data['scheduling_resource_id'],
                Carbon::parse($data['starts_at_utc'], 'UTC'),
                Carbon::parse($data['ends_at_utc'], 'UTC'),
            );
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        }

        return response()->json([
            'hold_id' => $hold->id,
            'expires_at' => $hold->expires_at->toIso8601String(),
        ]);
    }

    public function submit(Request $request, AppointmentInboundService $inbound, SchedulingEntitlementService $entitlements): JsonResponse
    {
        $tenant = currentTenant();
        abort_if($tenant === null, 404);
        if (! $entitlements->tenantCanUseScheduling($tenant)) {
            abort(404);
        }

        $data = $request->validate([
            'hold_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'message' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $hold = AppointmentHold::query()
            ->where('tenant_id', $tenant->id)
            ->whereKey((int) $data['hold_id'])
            ->firstOrFail();

        try {
            $crm = $inbound->submitHold(
                $tenant,
                $hold,
                $data['name'],
                $data['phone'] ?? null,
                $data['email'] ?? null,
                $data['message'],
            );
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        }

        return response()->json([
            'success' => true,
            'crm_request_id' => $crm->id,
        ]);
    }
}
