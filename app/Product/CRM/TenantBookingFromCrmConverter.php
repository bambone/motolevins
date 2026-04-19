<?php

namespace App\Product\CRM;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\CrmRequest;
use App\Models\Lead;
use App\Models\Motorcycle;
use App\Models\RentalUnit;
use App\Models\Tenant;
use App\MotorcyclePricing\BookingPricingHydrator;
use App\MotorcyclePricing\MotorcycleBookingPricingPolicy;
use App\MotorcyclePricing\RentalPricingDuration;
use App\Services\AvailabilityService;
use App\Services\PricingService;
use App\Support\PhoneNormalizer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Создаёт запись {@see Booking} по данным {@see Lead}:
 * - при конверсии CRM tenant_booking (источник {@see CrmRequest::STATUS_CONVERTED}) — {@see $source} crm_converted;
 * - при статусе обращения «Подтверждена» в кабинете — lead_confirmed (календарь и «Бронирования» читают {@see Booking}).
 */
final class TenantBookingFromCrmConverter
{
    public function __construct(
        private readonly AvailabilityService $availabilityService,
        private readonly PricingService $pricingService,
        private readonly BookingPricingHydrator $bookingPricingHydrator,
        private readonly MotorcycleBookingPricingPolicy $motorcycleBookingPricingPolicy,
    ) {}

    /**
     * Идемпотентно: при повторном вызове не создаёт вторую бронь.
     *
     * @return array{0: ?Booking, 1: bool} Бронь (если есть) и флаг «только что создана».
     */
    public function createConfirmedBookingIfMissing(CrmRequest $crm): array
    {
        if ($crm->tenant_id === null) {
            return [null, false];
        }

        if (($crm->request_type ?? '') !== 'tenant_booking') {
            return [null, false];
        }

        if ($crm->status !== CrmRequest::STATUS_CONVERTED) {
            return [null, false];
        }

        $lead = Lead::query()
            ->where('tenant_id', $crm->tenant_id)
            ->where('crm_request_id', $crm->id)
            ->orderByDesc('id')
            ->first();

        if ($lead === null) {
            Log::info('CRM convert: no lead for CRM request', ['crm_request_id' => $crm->id]);

            return [null, false];
        }

        return $this->insertConfirmedBookingForLead($lead, $crm, 'crm_converted');
    }

    /**
     * Публичный вход для {@see LeadObserver} и автосохранения в Filament: подтверждённое обращение с датами и техникой → бронь.
     */
    public function materializeConfirmedLeadBooking(Lead $lead): bool
    {
        if ($lead->status !== 'confirmed') {
            return false;
        }

        $crm = $lead->crm_request_id !== null
            ? CrmRequest::query()->find($lead->crm_request_id)
            : null;

        [, $didCreate] = $this->insertConfirmedBookingForLead($lead, $crm, 'lead_confirmed');

        return $didCreate;
    }

    /**
     * Для существующих заявок уже в статусе «Конверсия» (tenant_booking) без строки в bookings (разовый backfill).
     *
     * @return int Количество созданных броней
     */
    public function materializeAllConvertedTenantBookings(?int $onlyTenantId = null): int
    {
        $q = CrmRequest::query()
            ->where('status', CrmRequest::STATUS_CONVERTED)
            ->where('request_type', 'tenant_booking')
            ->whereNotNull('tenant_id');

        if ($onlyTenantId !== null) {
            $q->where('tenant_id', $onlyTenantId);
        }

        $created = 0;
        foreach ($q->cursor() as $crm) {
            [, $didCreate] = $this->createConfirmedBookingIfMissing($crm);
            if ($didCreate) {
                $created++;
            }
        }

        return $created;
    }

    /**
     * Обращения в статусе «Подтверждена» без брони (backfill после смены логики или ручного подтверждения в списке).
     *
     * @return int Количество созданных броней
     */
    public function materializeAllConfirmedLeadsWithoutBooking(?int $onlyTenantId = null): int
    {
        $q = Lead::query()
            ->where('status', 'confirmed')
            ->whereNotNull('tenant_id')
            ->whereDoesntHave('bookings');

        if ($onlyTenantId !== null) {
            $q->where('tenant_id', $onlyTenantId);
        }

        $created = 0;
        foreach ($q->cursor() as $lead) {
            if ($this->materializeConfirmedLeadBooking($lead)) {
                $created++;
            }
        }

        return $created;
    }

    /**
     * @return array{0: ?Booking, 1: bool}
     */
    private function insertConfirmedBookingForLead(Lead $lead, ?CrmRequest $crm, string $source): array
    {
        if ($lead->tenant_id === null) {
            return [null, false];
        }

        $existing = Booking::query()->where('lead_id', $lead->id)->first();
        if ($existing !== null) {
            return [$existing, false];
        }

        if ($lead->motorcycle_id === null || $lead->rental_date_from === null || $lead->rental_date_to === null) {
            Log::info('Booking materialize: lead missing motorcycle or rental dates', ['lead_id' => $lead->id]);

            return [null, false];
        }

        $motorcycle = Motorcycle::query()
            ->where('tenant_id', $lead->tenant_id)
            ->whereKey($lead->motorcycle_id)
            ->first();

        if ($motorcycle === null) {
            Log::warning('Booking materialize: motorcycle not found', [
                'lead_id' => $lead->id,
                'motorcycle_id' => $lead->motorcycle_id,
            ]);

            return [null, false];
        }

        $startDate = $lead->rental_date_from->toDateString();
        $endDate = $lead->rental_date_to->toDateString();

        $tenant = Tenant::query()->find($lead->tenant_id);
        $tz = $tenant?->timezone !== null && $tenant->timezone !== ''
            ? (string) $tenant->timezone
            : (string) config('app.timezone', 'UTC');

        $startAt = Carbon::parse($startDate, $tz)->startOfDay();
        $endAt = Carbon::parse($endDate, $tz)->endOfDay();

        $unit = RentalUnit::query()
            ->where('tenant_id', $lead->tenant_id)
            ->where('motorcycle_id', $motorcycle->id)
            ->where('status', 'active')
            ->orderBy('id')
            ->first();

        $startDay = Carbon::parse($startDate, $tz)->startOfDay();
        $endDay = Carbon::parse($endDate, $tz)->startOfDay();
        $days = RentalPricingDuration::inclusiveCalendarDays($startDay, $endDay);

        if (! $this->motorcycleBookingPricingPolicy->canMaterializeConfirmedBookingWithEngineTotal($motorcycle, $days)) {
            Log::warning('Booking materialize: skipped — motorcycle quote is not ok (on_request, invalid profile, etc.)', [
                'lead_id' => $lead->id,
                'motorcycle_id' => $motorcycle->id,
                'days' => $days,
            ]);

            return [null, false];
        }

        $target = $unit ?? $motorcycle;
        $pricing = $this->pricingService->calculatePrice(
            $target,
            $startAt->copy()->startOfDay(),
            $endAt->copy()->startOfDay(),
            'daily',
            [],
        );
        $addonLines = is_array($pricing['addons'] ?? null) ? $pricing['addons'] : [];
        $v2 = $this->bookingPricingHydrator->bookingPricingAttributes($motorcycle, $days, $pricing, $addonLines);
        $basePrice = (int) ($pricing['base_price'] ?? 0);
        $ppdSnap = $days > 0 ? (int) round($basePrice / $days) : 0;

        $phone = $lead->phone ?: ($crm?->phone ?? '');
        $name = $lead->name ?: ($crm?->name ?? '') ?: '';

        /** @var Booking $booking */
        $booking = Booking::query()->create([
            'tenant_id' => $lead->tenant_id,
            'lead_id' => $lead->id,
            'customer_id' => $lead->customer_id,
            'motorcycle_id' => $motorcycle->id,
            'rental_unit_id' => $unit?->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'status' => BookingStatus::CONFIRMED,
            'price_per_day_snapshot' => $ppdSnap,
            'total_price' => (int) ($pricing['total'] ?? 0),
            'pricing_snapshot_json' => $v2['pricing_snapshot_json'],
            'pricing_snapshot_schema_version' => $v2['pricing_snapshot_schema_version'],
            'currency' => $v2['currency'],
            'rental_total_minor' => $v2['rental_total_minor'],
            'deposit_amount_minor' => $v2['deposit_amount_minor'],
            'payable_now_minor' => $v2['payable_now_minor'],
            'selected_tariff_id' => $v2['selected_tariff_id'],
            'selected_tariff_kind' => $v2['selected_tariff_kind'],
            'deposit_amount' => (int) ($pricing['deposit'] ?? 0),
            'customer_name' => $name,
            'phone' => $phone,
            'phone_normalized' => PhoneNormalizer::normalize($phone),
            'source' => $source,
            'customer_comment' => $lead->comment,
        ]);

        if ($booking->rental_unit_id !== null) {
            try {
                $this->availabilityService->blockForBooking($booking);
            } catch (\Throwable $e) {
                Log::warning('Booking materialize: blockForBooking failed', [
                    'booking_id' => $booking->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return [$booking, true];
    }
}
