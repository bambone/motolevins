<?php

namespace Tests\Feature\Tenant;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Motorcycle;
use App\Support\PhoneNormalizer;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

class TenantPublicBookingAvailabilityApiTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    protected function postTenantJson(string $host, string $path, array $payload): TestResponse
    {
        $path = str_starts_with($path, '/') ? $path : '/'.$path;

        return $this->call('POST', 'http://'.$host.$path, [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($payload, JSON_THROW_ON_ERROR));
    }

    public function test_catalog_availability_reflects_overlapping_booking_without_rental_units(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $tenant = $this->createTenantWithActiveDomain('availcat');
        $bike = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Cat Bike',
            'slug' => 'cat-bike',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 4000,
        ]);

        Booking::query()->create([
            'tenant_id' => $tenant->id,
            'bike_id' => null,
            'motorcycle_id' => $bike->id,
            'rental_unit_id' => null,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-03',
            'start_at' => '2026-06-01 00:00:00',
            'end_at' => '2026-06-03 23:59:59',
            'status' => BookingStatus::CONFIRMED,
            'price_per_day_snapshot' => 4000,
            'total_price' => 12000,
            'customer_name' => 'A',
            'phone' => '+79991112233',
            'phone_normalized' => PhoneNormalizer::normalize('+79991112233'),
            'source' => 'test',
        ]);

        $host = $this->tenancyHostForSlug('availcat');
        $response = $this->postTenantJson($host, '/api/tenant/booking/catalog-availability', [
            'start_date' => '2026-06-02',
            'end_date' => '2026-06-02',
            'motorcycle_ids' => [$bike->id],
        ]);

        $response->assertOk();
        $response->assertJsonPath('availability.'.(string) $bike->id, false);
    }

    public function test_catalog_adjacent_dates_do_not_overlap_previous_booking(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $tenant = $this->createTenantWithActiveDomain('availadj');
        $bike = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Adj Bike',
            'slug' => 'adj-bike',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 4000,
        ]);

        Booking::query()->create([
            'tenant_id' => $tenant->id,
            'bike_id' => null,
            'motorcycle_id' => $bike->id,
            'rental_unit_id' => null,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-03',
            'start_at' => '2026-07-01 00:00:00',
            'end_at' => '2026-07-03 23:59:59',
            'status' => BookingStatus::PENDING,
            'price_per_day_snapshot' => 4000,
            'total_price' => 12000,
            'customer_name' => 'A',
            'phone' => '+79991112233',
            'phone_normalized' => PhoneNormalizer::normalize('+79991112233'),
            'source' => 'test',
        ]);

        $host = $this->tenancyHostForSlug('availadj');
        $response = $this->postTenantJson($host, '/api/tenant/booking/catalog-availability', [
            'start_date' => '2026-07-04',
            'end_date' => '2026-07-05',
            'motorcycle_ids' => [$bike->id],
        ]);

        $response->assertOk();
        $response->assertJsonPath('availability.'.(string) $bike->id, true);
    }

    public function test_motorcycle_hints_detects_same_phone_different_format(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $tenant = $this->createTenantWithActiveDomain('availhint');
        $bike = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Hint Bike',
            'slug' => 'hint-bike',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 4000,
        ]);

        Booking::query()->create([
            'tenant_id' => $tenant->id,
            'bike_id' => null,
            'motorcycle_id' => $bike->id,
            'rental_unit_id' => null,
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-12',
            'start_at' => '2026-08-10 00:00:00',
            'end_at' => '2026-08-12 23:59:59',
            'status' => BookingStatus::AWAITING_PAYMENT,
            'price_per_day_snapshot' => 4000,
            'total_price' => 12000,
            'customer_name' => 'A',
            'phone' => '+7 (999) 111-22-33',
            'phone_normalized' => PhoneNormalizer::normalize('+79991112233'),
            'source' => 'test',
        ]);

        $host = $this->tenancyHostForSlug('availhint');
        $response = $this->postTenantJson($host, '/api/tenant/booking/motorcycle-calendar-hints', [
            'motorcycle_id' => $bike->id,
            'range_from' => '2026-08-01',
            'range_to' => '2026-08-31',
            'selected_start' => '2026-08-10',
            'selected_end' => '2026-08-12',
            'phone' => '8 999 111 22 33',
        ]);

        $response->assertOk();
        $response->assertJsonPath('already_booked_by_phone', true);
    }

    public function test_motorcycle_hints_empty_phone_skips_self_check(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $tenant = $this->createTenantWithActiveDomain('availnop');
        $bike = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Nop Bike',
            'slug' => 'nop-bike',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 4000,
        ]);

        $host = $this->tenancyHostForSlug('availnop');
        $response = $this->postTenantJson($host, '/api/tenant/booking/motorcycle-calendar-hints', [
            'motorcycle_id' => $bike->id,
            'range_from' => '2026-09-01',
            'range_to' => '2026-09-20',
            'selected_start' => '2026-09-05',
            'selected_end' => '2026-09-06',
            'phone' => '',
        ]);

        $response->assertOk();
        $response->assertJsonPath('already_booked_by_phone', false);
    }
}
