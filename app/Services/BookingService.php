<?php

namespace App\Services;

use App\DTO\BookingData;
use App\Enums\BookingStatus;
use App\Jobs\SendBookingTelegramNotification;
use App\Models\Bike;
use App\Models\Booking;
use Carbon\Carbon;
use Exception;

class BookingService
{
    /**
     * @throws Exception
     */
    public function createBooking(BookingData $data): Booking
    {
        $bike = Bike::findOrFail($data->bike_id);

        if (!$this->isAvailable($bike->id, $data->start_date, $data->end_date)) {
            throw new Exception('The selected bike is not available for these dates.');
        }

        $startDate = Carbon::parse($data->start_date);
        $endDate = Carbon::parse($data->end_date);
        $days = $startDate->diffInDays($endDate) + 1; // Inclusive overlap logic means day 1 to day 1 = 1 day
        
        $totalPrice = $days * $bike->price_per_day;

        /** @var Booking $booking */
        $booking = Booking::create([
            'bike_id' => $bike->id,
            'start_date' => $data->start_date,
            'end_date' => $data->end_date,
            'status' => BookingStatus::PENDING,
            'price_per_day_snapshot' => $bike->price_per_day,
            'total_price' => $totalPrice,
            'customer_name' => $data->customer_name,
            'phone' => $data->phone,
            'phone_normalized' => $this->normalizePhone($data->phone),
            'source' => $data->source,
            'customer_comment' => $data->customer_comment,
        ]);

        SendBookingTelegramNotification::dispatch($booking);

        return $booking;
    }

    public function isAvailable(int $bikeId, string $startDate, string $endDate): bool
    {
        // [S1, E1] overlaps with [S2, E2] if max(S1, S2) <= min(E1, E2)
        // In SQL equivalent: start_date <= E2 AND end_date >= S2
        return !Booking::where('bike_id', $bikeId)
            ->whereIn('status', [BookingStatus::PENDING, BookingStatus::CONFIRMED])
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->exists();
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/[^0-9+]/', '', $phone);
    }
}
