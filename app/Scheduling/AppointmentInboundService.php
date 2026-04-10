<?php

declare(strict_types=1);

namespace App\Scheduling;

use App\Models\AppointmentHold;
use App\Models\BookableService;
use App\Models\CrmRequest;
use App\Models\Tenant;
use App\Product\CRM\Actions\CreateCrmRequestFromPublicForm;
use App\Product\CRM\DTO\PublicInboundContext;
use App\Product\CRM\DTO\PublicInboundSubmission;
use App\Scheduling\Enums\AppointmentHoldStatus;
use App\Scheduling\Enums\SchedulingScope;
use App\Scheduling\Occupancy\RentalAvailabilityBridge;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AppointmentInboundService
{
    public function __construct(
        private readonly CreateCrmRequestFromPublicForm $createCrmRequest,
        private readonly SlotEngineService $slotEngine,
        private readonly SchedulingEntitlementService $entitlements,
        private readonly OutboundAppointmentCalendarRecorder $outboundCalendarRecorder,
        private readonly SchedulingIntegrationGate $integrationGate,
        private readonly SchedulingStaleBusyEvaluator $staleBusyEvaluator,
        private readonly RentalAvailabilityBridge $rentalAvailabilityBridge,
    ) {}

    public function createHold(
        Tenant $tenant,
        BookableService $service,
        int $schedulingResourceId,
        Carbon $startsAtUtc,
        Carbon $endsAtUtc,
        int $ttlMinutes = 15,
    ): AppointmentHold {
        if (! $this->entitlements->tenantCanUseScheduling($tenant)) {
            throw ValidationException::withMessages(['service' => 'Scheduling is not available.']);
        }

        if ($this->integrationGate->blocksPublicAppointmentSlots($tenant)) {
            throw ValidationException::withMessages(['scheduling' => 'Calendar integration is in an error state.']);
        }

        $rangeStart = $startsAtUtc->copy()->subDay();
        $rangeEnd = $endsAtUtc->copy()->addDay();
        $target = $service->schedulingTarget;
        if ($target !== null
            && $this->rentalAvailabilityBridge->staleDataBlocksNewSlots($target, $tenant)
            && $this->staleBusyEvaluator->subscriptionsAreStaleForTarget($target)) {
            throw ValidationException::withMessages(['slot' => 'Calendar busy data is stale; new holds are blocked.']);
        }

        return DB::transaction(function () use (
            $tenant,
            $service,
            $schedulingResourceId,
            $startsAtUtc,
            $endsAtUtc,
            $rangeStart,
            $rangeEnd,
            $ttlMinutes,
        ): AppointmentHold {
            $overlap = AppointmentHold::query()
                ->where('tenant_id', $tenant->id)
                ->where('scheduling_resource_id', $schedulingResourceId)
                ->where('bookable_service_id', $service->id)
                ->where('starts_at_utc', '<', $endsAtUtc)
                ->where('ends_at_utc', '>', $startsAtUtc)
                ->where(function ($q): void {
                    $q->whereIn('status', [AppointmentHoldStatus::Pending, AppointmentHoldStatus::Confirmed])
                        ->orWhere(function ($q2): void {
                            $q2->where('status', AppointmentHoldStatus::Hold)
                                ->where('expires_at', '>', Carbon::now('UTC'));
                        });
                })
                ->lockForUpdate()
                ->exists();

            if ($overlap) {
                throw ValidationException::withMessages(['slot' => 'This slot is already held or booked.']);
            }

            $slots = $this->slotEngine->slotsForBookableService($service, $rangeStart, $rangeEnd);
            $match = false;
            foreach ($slots as $slot) {
                if ((int) $slot['scheduling_resource_id'] === $schedulingResourceId
                    && $slot['starts_at_utc'] === $startsAtUtc->toIso8601String()
                    && $slot['ends_at_utc'] === $endsAtUtc->toIso8601String()) {
                    $match = true;
                    break;
                }
            }
            if (! $match) {
                throw ValidationException::withMessages(['slot' => 'The selected slot is no longer available.']);
            }

            return AppointmentHold::query()->create([
                'scheduling_scope' => SchedulingScope::Tenant,
                'tenant_id' => $tenant->id,
                'bookable_service_id' => $service->id,
                'scheduling_resource_id' => $schedulingResourceId,
                'starts_at_utc' => $startsAtUtc,
                'ends_at_utc' => $endsAtUtc,
                'status' => AppointmentHoldStatus::Hold,
                'source' => 'public_form',
                'expires_at' => Carbon::now('UTC')->addMinutes($ttlMinutes),
            ]);
        });
    }

    public function submitHold(
        Tenant $tenant,
        AppointmentHold $hold,
        string $name,
        ?string $phone,
        ?string $email,
        string $message,
    ): CrmRequest {
        if ($hold->tenant_id !== $tenant->id) {
            throw ValidationException::withMessages(['hold' => 'Invalid hold.']);
        }
        if ($hold->status !== AppointmentHoldStatus::Hold) {
            throw ValidationException::withMessages(['hold' => 'Hold is not active.']);
        }
        if (Carbon::now('UTC')->gte($hold->expires_at)) {
            $hold->update(['status' => AppointmentHoldStatus::Expired]);
            throw ValidationException::withMessages(['hold' => 'Hold expired.']);
        }

        if ($this->integrationGate->blocksPublicAppointmentSlots($tenant)) {
            throw ValidationException::withMessages(['scheduling' => 'Calendar integration is in an error state.']);
        }

        return DB::transaction(function () use ($tenant, $hold, $name, $phone, $email, $message): CrmRequest {
            $service = $hold->bookableService;
            if ($service === null) {
                throw ValidationException::withMessages(['hold' => 'Service missing.']);
            }

            $submission = new PublicInboundSubmission(
                requestType: 'tenant_appointment',
                name: $name,
                phone: $phone,
                email: $email,
                message: $message,
                source: 'scheduling_public',
                channel: 'web',
                payloadJson: array_filter([
                    'bookable_service_id' => $service->id,
                    'scheduling_target_id' => $service->schedulingTarget?->id,
                    'appointment_hold_id' => $hold->id,
                    'scheduling_resource_id' => $hold->scheduling_resource_id,
                    'requested_starts_at_utc' => $hold->starts_at_utc->toIso8601String(),
                    'requested_ends_at_utc' => $hold->ends_at_utc->toIso8601String(),
                    'slot_source' => 'public',
                ], fn (mixed $v): bool => $v !== null),
                landingPage: request()->header('referer'),
                referrer: request()->header('referer'),
                ip: request()->ip(),
                userAgent: request()->userAgent(),
            );

            $result = $this->createCrmRequest->handle(PublicInboundContext::tenant($tenant->id), $submission);

            $next = $service->requires_confirmation
                ? AppointmentHoldStatus::Pending
                : AppointmentHoldStatus::Confirmed;

            $hold->update([
                'crm_request_id' => $result->crmRequest->id,
                'status' => $next,
                'client_name' => $name,
                'client_email' => $email,
                'client_phone' => $phone,
            ]);

            $this->outboundCalendarRecorder->recordForHold($tenant, $hold, $result->crmRequest);

            return $result->crmRequest;
        });
    }
}
