<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Motorcycle;
use App\Models\RentalUnit;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-1 month', '+1 month');
        $end = (clone $start)->modify('+2 days');

        return [
            'tenant_id' => 1,
            'motorcycle_id' => null,
            'rental_unit_id' => null,
            'bike_id' => null,
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'start_at' => null,
            'end_at' => null,
            'status' => BookingStatus::PENDING,
            'price_per_day_snapshot' => 1000,
            'total_price' => 3000,
            'customer_name' => fake()->name(),
            'phone' => '+79990001122',
            'source' => 'factory',
        ];
    }

    public function occupying(): static
    {
        return $this->state(fn (): array => [
            'status' => fake()->randomElement([
                BookingStatus::PENDING,
                BookingStatus::AWAITING_PAYMENT,
                BookingStatus::CONFIRMED,
            ]),
        ]);
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (): array => ['tenant_id' => $tenant->id]);
    }

    public function withMotorcycle(Motorcycle $motorcycle): static
    {
        return $this->state(fn (): array => [
            'tenant_id' => $motorcycle->tenant_id,
            'motorcycle_id' => $motorcycle->id,
        ]);
    }

    public function withRentalUnit(RentalUnit $unit): static
    {
        return $this->state(fn (): array => [
            'tenant_id' => $unit->tenant_id,
            'rental_unit_id' => $unit->id,
            'motorcycle_id' => $unit->motorcycle_id,
        ]);
    }
}
