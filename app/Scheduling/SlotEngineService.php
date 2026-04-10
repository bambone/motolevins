<?php

declare(strict_types=1);

namespace App\Scheduling;

use App\Models\AppointmentHold;
use App\Models\AvailabilityException;
use App\Models\AvailabilityRule;
use App\Models\BookableService;
use App\Models\ExternalBusyBlock;
use App\Models\ManualBusyBlock;
use App\Models\SchedulingResource;
use App\Models\SchedulingTarget;
use App\Models\Tenant;
use App\Scheduling\Enums\AppointmentHoldStatus;
use App\Scheduling\Enums\AssignmentStrategy;
use App\Scheduling\Enums\AvailabilityExceptionType;
use App\Scheduling\Enums\AvailabilityRuleType;
use App\Scheduling\Enums\CalendarUsageMode;
use App\Scheduling\Enums\TentativeEventsPolicy;
use App\Scheduling\Enums\UnconfirmedRequestsPolicy;
use App\Scheduling\Occupancy\RentalAvailabilityBridge;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * Computes candidate slots: availability windows minus busy blocks, then service duration/step/buffers/lead/horizon.
 *
 * @phpstan-type SlotRow array{starts_at_utc: string, ends_at_utc: string, scheduling_resource_id: int, scheduling_resource_label: string}
 */
final class SlotEngineService
{
    public function __construct(
        private readonly SchedulingStaleBusyEvaluator $staleBusyEvaluator,
        private readonly SchedulingIntegrationGate $integrationGate,
        private readonly RentalAvailabilityBridge $rentalAvailabilityBridge,
    ) {}

    /**
     * @return list<SlotRow>
     */
    public function slotsForBookableService(
        BookableService $service,
        Carbon $rangeStartUtc,
        Carbon $rangeEndUtc,
    ): array {
        $target = $service->schedulingTarget;
        if ($target === null || ! $target->scheduling_enabled || ! $target->is_active || ! $service->is_active) {
            return [];
        }

        $tenant = Tenant::query()->find($service->tenant_id);
        if ($tenant === null) {
            return [];
        }

        if ($this->integrationGate->blocksPublicAppointmentSlots($tenant)) {
            return [];
        }

        if ($this->rentalAvailabilityBridge->staleDataBlocksNewSlots($target, $tenant)
            && $this->staleBusyEvaluator->subscriptionsAreStaleForTarget($target)) {
            return [];
        }

        $resources = $target->schedulingResources()
            ->where('scheduling_resources.is_active', true)
            ->orderByPivot('priority')
            ->get()
            ->filter(function (SchedulingResource $resource): bool {
                $raw = $resource->pivot->assignment_strategy ?? AssignmentStrategy::FirstAvailable->value;
                $strategy = AssignmentStrategy::tryFrom((string) $raw) ?? AssignmentStrategy::FirstAvailable;

                return $strategy !== AssignmentStrategy::ManualAfterRequest;
            });

        if ($resources->isEmpty()) {
            return [];
        }

        $now = Carbon::now('UTC');
        $minStart = $now->copy()->addMinutes($service->min_booking_notice_minutes);
        $maxEnd = $now->copy()->addDays($service->max_booking_horizon_days);

        $effectiveStart = $rangeStartUtc->copy()->max($minStart);
        $effectiveEnd = $rangeEndUtc->copy()->min($maxEnd);
        if ($effectiveEnd->lte($effectiveStart)) {
            return [];
        }

        $duration = $service->duration_minutes;
        $bufferBefore = $service->buffer_before_minutes;
        $bufferAfter = $service->buffer_after_minutes;
        $step = max(5, $service->slot_step_minutes);

        $unionSlots = [];

        foreach ($resources as $resource) {
            $windows = $this->availabilityWindowsUtc($resource, $target, $service, $effectiveStart, $effectiveEnd);
            $busy = $this->busyIntervalsUtc($resource, $target, $service, $effectiveStart, $effectiveEnd);
            $free = $this->subtractIntervals($windows, $busy);

            foreach ($free as $interval) {
                $cursor = $interval['start']->copy();
                $blockEnd = $interval['end'];
                while ($cursor->copy()->addMinutes($duration + $bufferBefore + $bufferAfter)->lte($blockEnd)) {
                    $slotStart = $cursor->copy()->addMinutes($bufferBefore);
                    $slotEnd = $slotStart->copy()->addMinutes($duration);
                    if ($slotEnd->copy()->addMinutes($bufferAfter)->lte($blockEnd)) {
                        $unionSlots[] = [
                            'starts_at_utc' => $slotStart->toIso8601String(),
                            'ends_at_utc' => $slotEnd->toIso8601String(),
                            'scheduling_resource_id' => $resource->id,
                            'scheduling_resource_label' => $resource->label,
                        ];
                    }
                    $cursor->addMinutes($step);
                }
            }
        }

        usort($unionSlots, fn (array $a, array $b): int => strcmp($a['starts_at_utc'], $b['starts_at_utc']));

        return $unionSlots;
    }

    /**
     * @return list<array{start: Carbon, end: Carbon}>
     */
    private function availabilityWindowsUtc(
        SchedulingResource $resource,
        SchedulingTarget $target,
        BookableService $service,
        Carbon $rangeStartUtc,
        Carbon $rangeEndUtc,
    ): array {
        $tz = $resource->timezone ?: 'UTC';
        $windows = [];

        $period = CarbonPeriod::create($rangeStartUtc->copy()->startOfDay(), '1 day', $rangeEndUtc->copy()->endOfDay());

        foreach ($period as $dayUtc) {
            $localDay = $dayUtc->copy()->timezone($tz)->startOfDay();
            $weekday = (int) $localDay->isoWeekday();

            $rules = AvailabilityRule::query()
                ->where('scheduling_resource_id', $resource->id)
                ->where('is_active', true)
                ->where('rule_type', AvailabilityRuleType::WeeklyOpen)
                ->where('weekday', $weekday)
                ->where(function ($q) use ($target) {
                    $q->whereNull('applies_to_scheduling_target_id')
                        ->orWhere('applies_to_scheduling_target_id', $target->id);
                })
                ->where(function ($q) use ($service) {
                    $q->whereNull('applies_to_bookable_service_id')
                        ->orWhere('applies_to_bookable_service_id', $service->id);
                })
                ->get();

            foreach ($rules as $rule) {
                if ($rule->valid_from && $localDay->toDateString() < $rule->valid_from->toDateString()) {
                    continue;
                }
                if ($rule->valid_to && $localDay->toDateString() > $rule->valid_to->toDateString()) {
                    continue;
                }

                $startLocal = $localDay->copy()->setTimeFromTimeString($rule->starts_at_local);
                $endLocal = $localDay->copy()->setTimeFromTimeString($rule->ends_at_local);
                if ($endLocal->lte($startLocal)) {
                    continue;
                }

                $startUtc = $startLocal->copy()->timezone('UTC');
                $endUtc = $endLocal->copy()->timezone('UTC');
                if ($endUtc->lte($rangeStartUtc) || $startUtc->gte($rangeEndUtc)) {
                    continue;
                }
                $windows[] = [
                    'start' => $startUtc->max($rangeStartUtc),
                    'end' => $endUtc->min($rangeEndUtc),
                ];
            }
        }

        $windows = $this->mergeIntervals($windows);

        $closedRules = AvailabilityRule::query()
            ->where('scheduling_resource_id', $resource->id)
            ->where('is_active', true)
            ->where('rule_type', AvailabilityRuleType::WeeklyClosed)
            ->get();

        $closedDayPeriod = CarbonPeriod::create($rangeStartUtc->copy()->startOfDay(), '1 day', $rangeEndUtc->copy()->endOfDay());

        foreach ($closedRules as $rule) {
            foreach ($closedDayPeriod as $dayUtc) {
                $localDay = $dayUtc->copy()->timezone($tz)->startOfDay();
                if ((int) $localDay->isoWeekday() !== $rule->weekday) {
                    continue;
                }
                if ($rule->valid_from && $localDay->toDateString() < $rule->valid_from->toDateString()) {
                    continue;
                }
                if ($rule->valid_to && $localDay->toDateString() > $rule->valid_to->toDateString()) {
                    continue;
                }
                $startLocal = $localDay->copy()->setTimeFromTimeString($rule->starts_at_local);
                $endLocal = $localDay->copy()->setTimeFromTimeString($rule->ends_at_local);
                if ($endLocal->lte($startLocal)) {
                    continue;
                }
                $startUtc = $startLocal->copy()->timezone('UTC');
                $endUtc = $endLocal->copy()->timezone('UTC');
                $windows = $this->subtractIntervals($windows, [['start' => $startUtc, 'end' => $endUtc]]);
            }
        }

        $exceptions = AvailabilityException::query()
            ->where('scheduling_resource_id', $resource->id)
            ->where(function ($q) use ($target) {
                $q->whereNull('scheduling_target_id')->orWhere('scheduling_target_id', $target->id);
            })
            ->where(function ($q) use ($service) {
                $q->whereNull('bookable_service_id')->orWhere('bookable_service_id', $service->id);
            })
            ->where('ends_at_utc', '>', $rangeStartUtc)
            ->where('starts_at_utc', '<', $rangeEndUtc)
            ->get();

        foreach ($exceptions as $ex) {
            if ($ex->exception_type === AvailabilityExceptionType::Closed) {
                $windows = $this->subtractIntervals($windows, [['start' => $ex->starts_at_utc, 'end' => $ex->ends_at_utc]]);
            } elseif ($ex->exception_type === AvailabilityExceptionType::Open) {
                $windows = $this->mergeIntervals(array_merge($windows, [
                    [
                        'start' => $ex->starts_at_utc->max($rangeStartUtc),
                        'end' => $ex->ends_at_utc->min($rangeEndUtc),
                    ],
                ]));
            }
        }

        return $windows;
    }

    /**
     * @return list<array{start: Carbon, end: Carbon}>
     */
    private function busyIntervalsUtc(
        SchedulingResource $resource,
        SchedulingTarget $target,
        BookableService $service,
        Carbon $rangeStartUtc,
        Carbon $rangeEndUtc,
    ): array {
        $busy = [];

        $mode = $target->calendar_usage_mode;
        // Write-only: исходящие события без подмешивания внешнего busy в публичный slot picker.
        $readExternalBusy = $target->external_busy_enabled && in_array($mode, [
            CalendarUsageMode::ReadBusyOnly,
            CalendarUsageMode::ReadBusyWriteEvents,
        ], true);

        if ($readExternalBusy) {
            $q = ExternalBusyBlock::query()
                ->where('scheduling_resource_id', $resource->id)
                ->where('ends_at_utc', '>', $rangeStartUtc)
                ->where('starts_at_utc', '<', $rangeEndUtc);
            foreach ($q->get() as $b) {
                if ($b->is_tentative && $resource->tentative_events_policy === TentativeEventsPolicy::TreatAsFree) {
                    continue;
                }
                $busy[] = ['start' => $b->starts_at_utc, 'end' => $b->ends_at_utc];
            }
        }

        $manual = ManualBusyBlock::query()
            ->where('scheduling_resource_id', $resource->id)
            ->where(function ($q) use ($target) {
                $q->whereNull('scheduling_target_id')->orWhere('scheduling_target_id', $target->id);
            })
            ->where('ends_at_utc', '>', $rangeStartUtc)
            ->where('starts_at_utc', '<', $rangeEndUtc)
            ->get();
        foreach ($manual as $m) {
            $busy[] = ['start' => $m->starts_at_utc, 'end' => $m->ends_at_utc];
        }

        if ($target->internal_busy_enabled) {
            $policy = $resource->unconfirmed_requests_policy;
            $holdQuery = AppointmentHold::query()
                ->where('scheduling_resource_id', $resource->id)
                ->where('bookable_service_id', $service->id)
                ->where('ends_at_utc', '>', $rangeStartUtc)
                ->where('starts_at_utc', '<', $rangeEndUtc);

            $holdQuery->where(function ($q) use ($policy) {
                if ($policy === UnconfirmedRequestsPolicy::Ignore) {
                    $q->whereRaw('1 = 0');
                } elseif ($policy === UnconfirmedRequestsPolicy::HoldOnly) {
                    $q->where('status', AppointmentHoldStatus::Hold)
                        ->where('expires_at', '>', Carbon::now('UTC'));
                } elseif ($policy === UnconfirmedRequestsPolicy::PendingIsBusy) {
                    $q->whereIn('status', [AppointmentHoldStatus::Hold, AppointmentHoldStatus::Pending]);
                } elseif ($policy === UnconfirmedRequestsPolicy::PendingAndConfirmedAreBusy) {
                    $q->whereIn('status', [
                        AppointmentHoldStatus::Hold,
                        AppointmentHoldStatus::Pending,
                        AppointmentHoldStatus::Confirmed,
                    ]);
                } else {
                    $q->where('status', AppointmentHoldStatus::Confirmed);
                }
            });

            foreach ($holdQuery->get() as $h) {
                $busy[] = ['start' => $h->starts_at_utc, 'end' => $h->ends_at_utc];
            }
        }

        return $this->mergeIntervals($busy);
    }

    /**
     * @param  list<array{start: Carbon, end: Carbon}>  $windows
     * @return list<array{start: Carbon, end: Carbon}>
     */
    private function mergeIntervals(array $windows): array
    {
        if ($windows === []) {
            return [];
        }
        usort($windows, fn ($a, $b) => $a['start']->timestamp <=> $b['start']->timestamp);
        $merged = [];
        $cur = $windows[0];
        for ($i = 1, $n = count($windows); $i < $n; $i++) {
            $w = $windows[$i];
            if ($w['start']->lte($cur['end'])) {
                $cur['end'] = $cur['end']->max($w['end']);
            } else {
                $merged[] = $cur;
                $cur = $w;
            }
        }
        $merged[] = $cur;

        return $merged;
    }

    /**
     * @param  list<array{start: Carbon, end: Carbon}>  $windows
     * @param  list<array{start: Carbon, end: Carbon}>  $cuts
     * @return list<array{start: Carbon, end: Carbon}>
     */
    private function subtractIntervals(array $windows, array $cuts): array
    {
        if ($windows === []) {
            return [];
        }
        if ($cuts === []) {
            return $windows;
        }
        $cuts = $this->mergeIntervals($cuts);
        $out = [];
        foreach ($windows as $w) {
            $segStart = $w['start'];
            $segEnd = $w['end'];
            foreach ($cuts as $c) {
                if ($c['end']->lte($segStart) || $c['start']->gte($segEnd)) {
                    continue;
                }
                if ($c['start']->gt($segStart)) {
                    $out[] = ['start' => $segStart, 'end' => $c['start']->min($segEnd)];
                }
                if ($c['end']->gte($segEnd)) {
                    $segStart = $segEnd;
                    break;
                }
                $segStart = $c['end']->max($segStart);
            }
            if ($segEnd->gt($segStart)) {
                $out[] = ['start' => $segStart, 'end' => $segEnd];
            }
        }

        return $this->mergeIntervals($out);
    }
}
