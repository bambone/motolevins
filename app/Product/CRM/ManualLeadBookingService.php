<?php

namespace App\Product\CRM;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\CrmRequest;
use App\Models\Lead;
use App\Models\Motorcycle;
use App\Models\RentalUnit;
use App\Models\Tenant;
use App\Product\CRM\Actions\CreateCrmRequestFromPublicForm;
use App\Product\CRM\DTO\ManualBookingCreateData;
use App\Product\CRM\DTO\ManualLeadCreateData;
use App\Product\CRM\DTO\ManualOperatorResult;
use App\Product\CRM\DTO\PublicInboundContext;
use App\Product\CRM\DTO\PublicInboundSubmission;
use App\Services\AvailabilityService;
use App\Support\Phone\IntlPhoneNormalizer;
use App\Support\PhoneNormalizer;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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

    public function __construct(
        private readonly CreateCrmRequestFromPublicForm $createCrmRequestFromPublicForm,
        private readonly AvailabilityService $availabilityService,
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

            $booking = $this->insertBookingForLead(
                tenantId: $data->tenantId,
                lead: $lead,
                motorcycleId: $data->motorcycleId,
                rentalUnitId: $data->rentalUnitId,
                startDateYmd: $data->startDateYmd,
                endDateYmd: $data->endDateYmd,
            );

            return new ManualOperatorResult(lead: $lead, crmRequest: $crm, booking: $booking);
        });
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

        $ppd = (int) $motorcycle->price_per_day;
        $startDay = Carbon::parse($startDateYmd, $tz)->startOfDay();
        $endDay = Carbon::parse($endDateYmd, $tz)->startOfDay();
        $days = max(1, (int) $startDay->diffInDays($endDay) + 1);
        $total = $days * $ppd;

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
            'price_per_day_snapshot' => $ppd,
            'total_price' => $total,
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
