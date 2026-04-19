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
use App\Product\CRM\Actions\CreateCrmRequestFromPublicForm;
use App\Product\CRM\DTO\ManualBookingCreateData;
use App\Product\CRM\DTO\ManualLeadCreateData;
use App\Product\CRM\DTO\ManualOperatorResult;
use App\Product\CRM\DTO\PublicInboundContext;
use App\Product\CRM\DTO\PublicInboundSubmission;
use App\Services\AvailabilityService;
use App\Services\PricingService;
use App\Support\Phone\IntlPhoneNormalizer;
use App\Support\PhoneNormalizer;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Ручное создание {@see Lead} / {@see CrmRequest} / {@see Booking} в tenant scope (кабинет клиента).
 *
 * Лиды создаются со статусом {@see Lead::status} <code>in_progress</code>, чтобы {@see LeadObserver}
 * не создавал вторую бронь через {@see TenantBookingFromCrmConverter} (триггер — только <code>confirmed</code>).
 */
final class ManualLeadBookingService
{
    public const REQUEST_TYPE_TENANT_OPERATOR = 'tenant_operator';

    /**
     * Окно защиты от случайного двойного submit (одинаковый payload, один оператор).
     */
    private const ACCIDENTAL_DUPLICATE_TTL_SECONDS = 12;

    public function __construct(
        private readonly CreateCrmRequestFromPublicForm $createCrmRequestFromPublicForm,
        private readonly AvailabilityService $availabilityService,
        private readonly PricingService $pricingService,
        private readonly BookingPricingHydrator $bookingPricingHydrator,
        private readonly MotorcycleBookingPricingPolicy $motorcycleBookingPricingPolicy,
    ) {}

    private function normalizedOperatorPhone(?string $raw): string
    {
        return IntlPhoneNormalizer::normalizePhone((string) ($raw ?? ''));
    }

    private function optionalOperatorEmail(?string $raw): ?string
    {
        $e = is_string($raw) ? trim($raw) : '';

        return $e === '' ? null : $e;
    }

    public function createManualLead(ManualLeadCreateData $data): ManualOperatorResult
    {
        $this->assertTenantMatchesContext($data->tenantId);

        if ($data->createBooking) {
            $this->assertLeadBookingFieldsPresent(
                $data->motorcycleId,
                $data->rentalUnitId,
                $data->rentalDateFromYmd,
                $data->rentalDateToYmd,
            );
        }

        return DB::transaction(function () use ($data): ManualOperatorResult {
            Gate::authorize('create', Lead::class);

            if ($data->createCrm) {
                Gate::authorize('create', CrmRequest::class);
            }

            if ($data->createBooking) {
                Gate::authorize('create', Booking::class);
            }

            $this->preventAccidentalDuplicateOperatorSubmission('manual_lead', [
                'tenant_id' => $data->tenantId,
                'name' => $data->name,
                'phone' => $this->normalizedOperatorPhone($data->phone),
                'email' => $this->optionalOperatorEmail($data->email),
                'comment' => $data->comment,
                'create_crm' => $data->createCrm,
                'create_booking' => $data->createBooking,
                'motorcycle_id' => $data->motorcycleId,
                'rental_from' => $data->rentalDateFromYmd,
                'rental_to' => $data->rentalDateToYmd,
                'rental_unit_id' => $data->rentalUnitId,
            ]);

            Log::info('crm.manual.operator_lead_submit', [
                'tenant_id' => $data->tenantId,
                'user_id' => Auth::id(),
                'create_crm' => $data->createCrm,
                'create_booking' => $data->createBooking,
            ]);

            $crm = null;
            if ($data->createCrm) {
                $submission = $this->buildInboundSubmissionForManualLead($data);
                $ctx = PublicInboundContext::tenant($data->tenantId);
                $crmResult = $this->createCrmRequestFromPublicForm->handle($ctx, $submission);
                $crm = $crmResult->crmRequest;
                $lead = $crmResult->lead;
                if ($lead === null) {
                    throw new \RuntimeException('CRM inbound did not create a tenant lead.');
                }
            } else {
                $lead = Lead::query()->create([
                    'tenant_id' => $data->tenantId,
                    'crm_request_id' => null,
                    'name' => $data->name,
                    'phone' => $this->normalizedOperatorPhone($data->phone),
                    'email' => $this->optionalOperatorEmail($data->email),
                    'comment' => $data->comment,
                    'motorcycle_id' => $data->motorcycleId,
                    'rental_date_from' => $data->rentalDateFromYmd,
                    'rental_date_to' => $data->rentalDateToYmd,
                    'source' => 'manual',
                    'page_url' => '/admin',
                    'status' => 'in_progress',
                ]);
            }

            $booking = null;
            if ($data->createBooking) {
                $booking = $this->insertBookingForLead(
                    tenantId: $data->tenantId,
                    lead: $lead,
                    motorcycleId: (int) $data->motorcycleId,
                    rentalUnitId: (int) $data->rentalUnitId,
                    startDateYmd: (string) $data->rentalDateFromYmd,
                    endDateYmd: (string) $data->rentalDateToYmd,
                );
            }

            Log::info('crm.manual.operator_lead_success', [
                'tenant_id' => $data->tenantId,
                'user_id' => Auth::id(),
                'lead_id' => $lead->id,
                'crm_request_id' => $crm?->id,
                'booking_id' => $booking?->id,
            ]);

            return new ManualOperatorResult(lead: $lead, crmRequest: $crm, booking: $booking);
        });
    }

    public function createManualBooking(ManualBookingCreateData $data): ManualOperatorResult
    {
        $this->assertTenantMatchesContext($data->tenantId);
        $this->validateBookingDateRange($data->startDateYmd, $data->endDateYmd);

        if ($data->existingLeadId === null && ! $data->createLead) {
            throw ValidationException::withMessages([
                'existing_lead_id' => 'Укажите существующее обращение или включите создание нового.',
            ]);
        }

        if ($data->existingLeadId !== null && $data->createCrm) {
            throw ValidationException::withMessages([
                'create_crm' => 'Для существующего обращения создание CRM через эту форму недоступно.',
            ]);
        }

        return DB::transaction(function () use ($data): ManualOperatorResult {
            Gate::authorize('create', Booking::class);

            $lead = null;
            $crm = null;

            if ($data->existingLeadId !== null) {
                $lead = Lead::query()
                    ->where('tenant_id', $data->tenantId)
                    ->whereKey($data->existingLeadId)
                    ->firstOrFail();

                Gate::authorize('update', $lead);
            } elseif ($data->createLead) {
                Gate::authorize('create', Lead::class);

                if ($data->createCrm) {
                    Gate::authorize('create', CrmRequest::class);
                    $submission = $this->buildInboundSubmissionForManualBooking($data);
                    $ctx = PublicInboundContext::tenant($data->tenantId);
                    $crmResult = $this->createCrmRequestFromPublicForm->handle($ctx, $submission);
                    $crm = $crmResult->crmRequest;
                    $lead = $crmResult->lead;
                    if ($lead === null) {
                        throw new \RuntimeException('CRM inbound did not create a tenant lead.');
                    }
                } else {
                    $lead = Lead::query()->create([
                        'tenant_id' => $data->tenantId,
                        'crm_request_id' => null,
                        'name' => $data->name,
                        'phone' => $this->normalizedOperatorPhone($data->phone),
                        'email' => $this->optionalOperatorEmail($data->email),
                        'comment' => $data->comment,
                        'motorcycle_id' => $data->motorcycleId,
                        'rental_date_from' => $data->startDateYmd,
                        'rental_date_to' => $data->endDateYmd,
                        'source' => 'manual',
                        'page_url' => '/admin',
                        'status' => 'in_progress',
                    ]);
                }
            }

            if ($lead === null) {
                throw new \RuntimeException('Lead is required to create a manual booking.');
            }

            $this->applyContactAndRentalHintsToLead($lead, $data);

            $this->preventAccidentalDuplicateOperatorSubmission('manual_booking', [
                'tenant_id' => $data->tenantId,
                'lead_id' => $lead->id,
                'existing_lead_id' => $data->existingLeadId,
                'create_lead' => $data->createLead,
                'create_crm' => $data->createCrm,
                'motorcycle_id' => $data->motorcycleId,
                'rental_unit_id' => $data->rentalUnitId,
                'start' => $data->startDateYmd,
                'end' => $data->endDateYmd,
                'name' => $data->name,
                'phone' => $this->normalizedOperatorPhone($data->phone),
            ]);

            Log::info('crm.manual.operator_booking_submit', [
                'tenant_id' => $data->tenantId,
                'user_id' => Auth::id(),
                'lead_id' => $lead->id,
                'existing_lead_id' => $data->existingLeadId,
                'create_lead' => $data->createLead,
                'create_crm' => $data->createCrm,
            ]);

            $booking = $this->insertBookingForLead(
                tenantId: $data->tenantId,
                lead: $lead,
                motorcycleId: $data->motorcycleId,
                rentalUnitId: $data->rentalUnitId,
                startDateYmd: $data->startDateYmd,
                endDateYmd: $data->endDateYmd,
            );

            Log::info('crm.manual.operator_booking_success', [
                'tenant_id' => $data->tenantId,
                'user_id' => Auth::id(),
                'lead_id' => $lead->id,
                'crm_request_id' => $crm?->id,
                'booking_id' => $booking->id,
            ]);

            return new ManualOperatorResult(lead: $lead, crmRequest: $crm, booking: $booking);
        });
    }

    /**
     * Защита от двойного клика / повторной отправки с тем же содержимым в коротком окне (UI + Livewire).
     *
     * @param  array<string, mixed>  $fingerprint
     */
    private function preventAccidentalDuplicateOperatorSubmission(string $channel, array $fingerprint): void
    {
        $userId = Auth::id();
        if ($userId === null) {
            return;
        }

        try {
            $encoded = json_encode($fingerprint, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $encoded = serialize($fingerprint);
        }

        $hash = hash('sha256', $encoded);
        $key = sprintf('crm:manual_op:%s:user:%s:%s', $channel, $userId, $hash);

        if (! Cache::add($key, microtime(true), self::ACCIDENTAL_DUPLICATE_TTL_SECONDS)) {
            throw ValidationException::withMessages([
                'name' => 'Запрос уже обрабатывается или только что был отправлен. Подождите несколько секунд и проверьте список — обращение могло уже появиться.',
            ]);
        }
    }

    private function assertTenantMatchesContext(int $tenantId): void
    {
        $tenant = currentTenant();
        if ($tenant === null || (int) $tenant->id !== $tenantId) {
            throw new AuthorizationException('Несоответствие контекста клиента.');
        }
    }

    private function assertLeadBookingFieldsPresent(
        ?int $motorcycleId,
        ?int $rentalUnitId,
        ?string $fromYmd,
        ?string $toYmd,
    ): void {
        $messages = [];
        if ($motorcycleId === null) {
            $messages['motorcycle_id'] = 'Выберите технику для бронирования.';
        }
        if ($rentalUnitId === null) {
            $messages['rental_unit_id'] = 'Выберите единицу парка.';
        }
        if ($fromYmd === null || $fromYmd === '') {
            $messages['rental_date_from'] = 'Укажите дату начала.';
        }
        if ($toYmd === null || $toYmd === '') {
            $messages['rental_date_to'] = 'Укажите дату окончания.';
        }
        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    private function validateBookingDateRange(string $startYmd, string $endYmd): void
    {
        try {
            $start = Carbon::parse($startYmd)->startOfDay();
            $end = Carbon::parse($endYmd)->startOfDay();
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'start_date' => 'Некорректный формат даты начала.',
                'end_date' => 'Некорректный формат даты окончания.',
            ]);
        }

        if ($end->lt($start)) {
            throw ValidationException::withMessages([
                'end_date' => 'Дата окончания не может быть раньше даты начала.',
            ]);
        }
    }

    private function buildInboundSubmissionForManualLead(ManualLeadCreateData $data): PublicInboundSubmission
    {
        $payload = array_filter([
            'motorcycle_id' => $data->motorcycleId,
            'rental_date_from' => $data->rentalDateFromYmd,
            'rental_date_to' => $data->rentalDateToYmd,
        ], fn (mixed $v): bool => $v !== null && $v !== '');

        return new PublicInboundSubmission(
            requestType: self::REQUEST_TYPE_TENANT_OPERATOR,
            name: $data->name,
            phone: $this->normalizedOperatorPhone($data->phone),
            email: $this->optionalOperatorEmail($data->email),
            message: $data->comment,
            source: 'manual',
            channel: 'phone',
            payloadJson: $payload,
            landingPage: '/admin',
            leadInitialStatus: 'in_progress',
        );
    }

    private function buildInboundSubmissionForManualBooking(ManualBookingCreateData $data): PublicInboundSubmission
    {
        $payload = [
            'motorcycle_id' => $data->motorcycleId,
            'rental_date_from' => $data->startDateYmd,
            'rental_date_to' => $data->endDateYmd,
        ];

        return new PublicInboundSubmission(
            requestType: self::REQUEST_TYPE_TENANT_OPERATOR,
            name: $data->name,
            phone: $this->normalizedOperatorPhone($data->phone),
            email: $this->optionalOperatorEmail($data->email),
            message: $data->comment,
            source: 'manual',
            channel: 'phone',
            payloadJson: $payload,
            landingPage: '/admin',
            leadInitialStatus: 'in_progress',
        );
    }

    private function applyContactAndRentalHintsToLead(Lead $lead, ManualBookingCreateData $data): void
    {
        $lead->forceFill([
            'motorcycle_id' => $data->motorcycleId,
            'rental_date_from' => $data->startDateYmd,
            'rental_date_to' => $data->endDateYmd,
            'name' => $data->name !== '' ? $data->name : $lead->name,
            'phone' => filled($data->phone) ? $this->normalizedOperatorPhone($data->phone) : $lead->phone,
            'email' => $this->optionalOperatorEmail($data->email) ?? $lead->email,
            'comment' => $data->comment ?? $lead->comment,
        ])->save();
    }

    private function insertBookingForLead(
        int $tenantId,
        Lead $lead,
        int $motorcycleId,
        int $rentalUnitId,
        string $startDateYmd,
        string $endDateYmd,
    ): Booking {
        $motorcycle = Motorcycle::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($motorcycleId)
            ->first();

        if ($motorcycle === null) {
            throw ValidationException::withMessages([
                'motorcycle_id' => 'Техника не найдена или не принадлежит этому клиенту.',
            ]);
        }

        $unit = RentalUnit::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($rentalUnitId)
            ->where('motorcycle_id', $motorcycleId)
            ->where('status', 'active')
            ->first();

        if ($unit === null) {
            throw ValidationException::withMessages([
                'rental_unit_id' => 'Единица парка не найдена, не активна или не соответствует выбранной технике.',
            ]);
        }

        $this->validateBookingDateRange($startDateYmd, $endDateYmd);

        $tenant = Tenant::query()->find($tenantId);
        $tz = $tenant?->timezone !== null && $tenant->timezone !== ''
            ? (string) $tenant->timezone
            : (string) config('app.timezone', 'UTC');

        $startAt = Carbon::parse($startDateYmd, $tz)->startOfDay();
        $endAt = Carbon::parse($endDateYmd, $tz)->endOfDay();

        if (! $this->availabilityService->isAvailable($unit, $startAt, $endAt)) {
            $this->throwAvailabilityConflict($unit, $startAt, $endAt);
        }

        $startDay = Carbon::parse($startDateYmd, $tz)->startOfDay();
        $endDay = Carbon::parse($endDateYmd, $tz)->startOfDay();
        $days = RentalPricingDuration::inclusiveCalendarDays($startDay, $endDay);

        $this->motorcycleBookingPricingPolicy->requireOkQuoteForConfirmedMaterialization($motorcycle, $days);

        $pricing = $this->pricingService->calculatePrice(
            $unit,
            $startAt->copy()->startOfDay(),
            $endAt->copy()->startOfDay(),
            'daily',
            [],
        );
        $addonLines = is_array($pricing['addons'] ?? null) ? $pricing['addons'] : [];
        $v2 = $this->bookingPricingHydrator->bookingPricingAttributes($motorcycle, $days, $pricing, $addonLines);
        $basePrice = (int) ($pricing['base_price'] ?? 0);
        $ppdSnap = $days > 0 ? (int) round($basePrice / $days) : 0;

        $phone = (string) ($lead->phone ?? '');
        $name = (string) ($lead->name ?? '');

        /** @var Booking $booking */
        $booking = Booking::query()->create([
            'tenant_id' => $tenantId,
            'lead_id' => $lead->id,
            'customer_id' => $lead->customer_id,
            'motorcycle_id' => $motorcycle->id,
            'rental_unit_id' => $unit->id,
            'start_date' => $startDateYmd,
            'end_date' => $endDateYmd,
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
            'source' => 'manual',
            'customer_comment' => $lead->comment,
        ]);

        $this->availabilityService->blockForBooking($booking);

        return $booking;
    }

    private function throwAvailabilityConflict(RentalUnit $unit, Carbon $start, Carbon $end): never
    {
        $conflicts = $this->availabilityService->getConflicts($unit, $start, $end);
        $first = $conflicts->first();
        $hint = '';
        if ($first !== null) {
            $hint = sprintf(
                ' Занято с %s по %s.',
                $first->starts_at->timezone(config('app.timezone'))->format('d.m.Y H:i'),
                $first->ends_at->timezone(config('app.timezone'))->format('d.m.Y H:i'),
            );
        }

        throw ValidationException::withMessages([
            'rental_unit_id' => 'Выбранная единица парка недоступна в этот период.'.$hint,
            'start_date' => 'Период пересекается с существующей бронью или блокировкой.',
            'end_date' => 'Период пересекается с существующей бронью или блокировкой.',
        ]);
    }
}
