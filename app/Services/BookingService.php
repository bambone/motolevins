<?php

namespace App\Services;

use App\DTO\BookingData;
use App\Enums\BookingStatus;
use App\Jobs\SendBookingTelegramNotification;
use App\Models\Addon;
use App\Models\Bike;
use App\Models\Booking;
use App\Models\BookingAddon;
use App\Models\Motorcycle;
use App\Models\RentalUnit;
use App\Models\Tenant;
use App\NotificationCenter\NotificationEventRecorder;
use App\NotificationCenter\Presenters\BookingNotificationPresenter;
use App\Support\PhoneNormalizer;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class BookingService
{
    public function __construct(
        protected AvailabilityService $availabilityService,
        protected NotificationEventRecorder $notificationRecorder,
        protected BookingNotificationPresenter $bookingNotifications,
    ) {}

    /**
     * Tenant-scoped bike booking (JSON API on tenant host). Requires {@see currentTenant()}.
     *
     * @throws Exception
     * @throws \RuntimeException when tenant context is missing
     */
    public function createBooking(BookingData $data): Booking
    {
        $booking = DB::transaction(function () use ($data): Booking {
            $tenant = currentTenant();
            if ($tenant === null) {
                throw new \RuntimeException('Bike booking requires an active tenant context.');
            }

            $bike = Bike::query()
                ->where('tenant_id', $tenant->id)
                ->whereKey($data->bike_id)
                ->firstOrFail();

            if (! $this->isAvailable($bike->id, $data->start_date, $data->end_date)) {
                throw new Exception('The selected bike is not available for these dates.');
            }

            $startDate = Carbon::parse($data->start_date);
            $endDate = Carbon::parse($data->end_date);
            $days = $startDate->diffInDays($endDate) + 1;

            $totalPrice = $days * $bike->price_per_day;

            /** @var Booking $booking */
            $booking = Booking::create([
                'tenant_id' => $bike->tenant_id,
                'bike_id' => $bike->id,
                'start_date' => $data->start_date,
                'end_date' => $data->end_date,
                'start_at' => $startDate->startOfDay(),
                'end_at' => $endDate->endOfDay(),
                'status' => BookingStatus::PENDING,
                'price_per_day_snapshot' => $bike->price_per_day,
                'total_price' => $totalPrice,
                'customer_name' => $data->customer_name,
                'phone' => $data->phone,
                'phone_normalized' => PhoneNormalizer::normalize($data->phone),
                'source' => $data->source,
                'customer_comment' => $data->customer_comment,
            ]);

            $this->dispatchBookingCreatedNotification($booking);

            return $booking;
        });

        if (config('notification_center.legacy_telegram_parallel')) {
            SendBookingTelegramNotification::dispatch($booking);
        }

        return $booking;
    }

    /**
     * Create booking from public checkout flow.
     *
     * Re-checks availability before insert (in addition to controller validation). Addon lines with
     * quantity &gt; 0 must resolve to an addon for this tenant or an {@see \InvalidArgumentException} is thrown
     * (avoids silent drops that desync line items vs the priced `pricing_snapshot` / total).
     */
    public function createPublicBooking(array $data): Booking
    {
        $tenantId = (int) ($data['tenant_id'] ?? 0);
        if ($tenantId < 1) {
            throw new \InvalidArgumentException('tenant_id is required for public booking.');
        }

        $booking = DB::transaction(function () use ($data, $tenantId): Booking {
            Motorcycle::query()
                ->where('tenant_id', $tenantId)
                ->whereKey((int) $data['motorcycle_id'])
                ->firstOrFail();

            [$rangeStart, $rangeEnd] = $this->publicBookingRangeBounds($data);

            if (! empty($data['rental_unit_id'])) {
                $unit = RentalUnit::query()
                    ->where('tenant_id', $tenantId)
                    ->whereKey((int) $data['rental_unit_id'])
                    ->firstOrFail();
                if ((int) $unit->motorcycle_id !== (int) $data['motorcycle_id']) {
                    throw new \InvalidArgumentException('Rental unit does not belong to the selected motorcycle.');
                }
                if (! $this->availabilityService->isAvailable($unit, $rangeStart, $rangeEnd)) {
                    throw new Exception(__('The selected dates are no longer available for this vehicle.'));
                }
            } elseif (! $this->isAvailableForMotorcycle((int) $data['motorcycle_id'], (string) $data['start_date'], (string) $data['end_date'])) {
                throw new Exception(__('The selected dates are no longer available.'));
            }

            $days = Carbon::parse($data['start_date'])->diffInDays(Carbon::parse($data['end_date'])) + 1;
            $basePrice = $data['pricing_snapshot']['base_price'] ?? 0;

            $booking = Booking::create([
                'tenant_id' => $tenantId,
                'motorcycle_id' => $data['motorcycle_id'],
                'rental_unit_id' => $data['rental_unit_id'] ?? null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'start_at' => $data['start_at'],
                'end_at' => $data['end_at'],
                'status' => BookingStatus::PENDING,
                'price_per_day_snapshot' => $days > 0 ? (int) round($basePrice / $days) : 0,
                'total_price' => $data['total_price'],
                'pricing_snapshot_json' => $data['pricing_snapshot'] ?? null,
                'deposit_amount' => $data['deposit_amount'] ?? 0,
                'payment_status' => 'pending',
                'customer_name' => $data['customer_name'],
                'phone' => $data['phone'],
                'preferred_contact_channel' => $data['preferred_contact_channel'] ?? null,
                'preferred_contact_value' => $data['preferred_contact_value'] ?? null,
                'visitor_contact_channels_json' => $data['visitor_contact_channels_json'] ?? null,
                'phone_normalized' => PhoneNormalizer::normalize($data['phone']),
                'source' => $data['source'] ?? 'public_booking',
                'customer_comment' => $data['customer_comment'] ?? null,
            ]);

            foreach ($data['addons'] ?? [] as $addonId => $qty) {
                $qty = is_numeric($qty) ? (int) $qty : 0;
                if ($qty <= 0) {
                    continue;
                }
                $addon = Addon::query()
                    ->where('tenant_id', $tenantId)
                    ->whereKey((int) $addonId)
                    ->first();
                if ($addon === null) {
                    throw new \InvalidArgumentException('Invalid or unavailable add-on for this booking.');
                }
                BookingAddon::create([
                    'booking_id' => $booking->id,
                    'addon_id' => $addon->id,
                    'quantity' => $qty,
                    'price_snapshot' => $addon->price,
                ]);
            }

            if ($booking->rental_unit_id) {
                $this->availabilityService->blockForBooking($booking);
            }

            $this->dispatchBookingCreatedNotification($booking);

            return $booking;
        });

        if (config('notification_center.legacy_telegram_parallel')) {
            SendBookingTelegramNotification::dispatch($booking);
        }

        return $booking;
    }

    private function dispatchBookingCreatedNotification(Booking $booking): void
    {
        $bookingId = (int) $booking->id;
        $tenantId = (int) $booking->tenant_id;
        DB::afterCommit(function () use ($bookingId, $tenantId): void {
            $fresh = Booking::query()->find($bookingId);
            $tenant = Tenant::query()->find($tenantId);
            if ($fresh === null || $tenant === null) {
                return;
            }

            $payload = $this->bookingNotifications->payloadForCreated($tenant, $fresh);
            $this->notificationRecorder->record(
                $tenantId,
                'booking.created',
                class_basename(Booking::class),
                $bookingId,
                $payload,
            );
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: Carbon, 1: Carbon}
     */
    private function publicBookingRangeBounds(array $data): array
    {
        $startRaw = $data['start_at'] ?? null;
        $endRaw = $data['end_at'] ?? null;

        if ($startRaw instanceof Carbon) {
            $start = $startRaw->copy()->startOfDay();
        } elseif ($startRaw instanceof \DateTimeInterface) {
            $start = Carbon::instance($startRaw)->startOfDay();
        } else {
            $start = Carbon::parse((string) $data['start_date'])->startOfDay();
        }

        if ($endRaw instanceof Carbon) {
            $end = $endRaw->copy()->endOfDay();
        } elseif ($endRaw instanceof \DateTimeInterface) {
            $end = Carbon::instance($endRaw)->endOfDay();
        } else {
            $end = Carbon::parse((string) $data['end_date'])->endOfDay();
        }

        return [$start, $end];
    }

    public function isAvailable(int $bikeId, string $startDate, string $endDate): bool
    {
        return ! Booking::where('bike_id', $bikeId)
            ->whereIn('status', Booking::occupyingStatusValues())
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->exists();
    }

    public function isAvailableForMotorcycle(int $motorcycleId, string $startDate, string $endDate): bool
    {
        $rentalUnits = RentalUnit::query()
            ->where('motorcycle_id', $motorcycleId)
            ->where('status', 'active')
            ->get();

        if ($rentalUnits->isEmpty()) {
            return ! Booking::where('motorcycle_id', $motorcycleId)
                ->whereIn('status', Booking::occupyingStatusValues())
                ->where('start_date', '<=', $endDate)
                ->where('end_date', '>=', $startDate)
                ->exists();
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        foreach ($rentalUnits as $unit) {
            if ($this->availabilityService->isAvailable($unit, $start, $end)) {
                return true;
            }
        }

        return false;
    }
}
