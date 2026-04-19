<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\Addon;
use App\Models\Motorcycle;
use App\Models\RentalUnit;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

final class PublicBookingMotorcycleAccessTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    private function postTenantJson(string $host, string $path, array $payload): TestResponse
    {
        $path = str_starts_with($path, '/') ? $path : '/'.$path;

        return $this->call('POST', 'http://'.$host.$path, [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($payload, JSON_THROW_ON_ERROR));
    }

    public function test_calculate_returns_422_when_motorcycle_hidden_from_catalog(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $tenant = $this->createTenantWithActiveDomain('pubacc_calc_hidden');
        $host = $this->tenancyHostForSlug('pubacc_calc_hidden');

        $bike = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Hidden Bike',
            'slug' => 'hidden-bike',
            'status' => 'available',
            'show_in_catalog' => false,
            'price_per_day' => 3000,
            'uses_fleet_units' => false,
        ]);

        $start = now()->addDays(5)->format('Y-m-d');
        $end = now()->addDays(6)->format('Y-m-d');

        $response = $this->postTenantJson($host, '/booking/calculate', [
            'motorcycle_id' => $bike->id,
            'rental_unit_id' => null,
            'start_date' => $start,
            'end_date' => $end,
            'addons' => [],
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('available', false);
    }

    public function test_calculate_returns_422_when_motorcycle_status_not_available(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $tenant = $this->createTenantWithActiveDomain('pubacc_calc_maint');
        $host = $this->tenancyHostForSlug('pubacc_calc_maint');

        $bike = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Maint Bike',
            'slug' => 'maint-bike',
            'status' => 'maintenance',
            'show_in_catalog' => true,
            'price_per_day' => 3000,
            'uses_fleet_units' => false,
        ]);

        $start = now()->addDays(5)->format('Y-m-d');
        $end = now()->addDays(6)->format('Y-m-d');

        $response = $this->postTenantJson($host, '/booking/calculate', [
            'motorcycle_id' => $bike->id,
            'rental_unit_id' => null,
            'start_date' => $start,
            'end_date' => $end,
            'addons' => [],
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('available', false);
    }

    public function test_booking_show_returns_404_when_motorcycle_not_allowed_for_public_booking(): void
    {
        $tenant = $this->createTenantWithActiveDomain('pubacc_show_404');
        $host = $this->tenancyHostForSlug('pubacc_show_404');

        Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'No Catalog',
            'slug' => 'no-catalog-bike',
            'status' => 'available',
            'show_in_catalog' => false,
            'price_per_day' => 3000,
            'uses_fleet_units' => false,
        ]);

        $this->call('GET', 'http://'.$host.'/booking/moto/no-catalog-bike')
            ->assertNotFound();
    }

    public function test_booking_show_renders_when_active_addons_exist(): void
    {
        $tenant = $this->createTenantWithActiveDomain('pubacc_show_addons');
        $host = $this->tenancyHostForSlug('pubacc_show_addons');

        Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Addon Bike',
            'slug' => 'addon-bike',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 3000,
            'uses_fleet_units' => false,
        ]);

        Addon::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Шлем',
            'type' => 'optional',
            'price' => 500,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->call('GET', 'http://'.$host.'/booking/moto/addon-bike')
            ->assertOk()
            ->assertSee('Шлем', false);
    }

    public function test_store_draft_persists_first_free_unit_when_auto_resolving_skips_busy_unit(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $tenant = $this->createTenantWithActiveDomain('pubacc_draft_unit');
        $host = $this->tenancyHostForSlug('pubacc_draft_unit');

        $m = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Fleet Bike',
            'slug' => 'fleet-bike',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 3000,
            'uses_fleet_units' => true,
        ]);

        $unitFirst = RentalUnit::query()->create([
            'tenant_id' => $tenant->id,
            'motorcycle_id' => $m->id,
            'status' => 'active',
        ]);
        $unitSecond = RentalUnit::query()->create([
            'tenant_id' => $tenant->id,
            'motorcycle_id' => $m->id,
            'status' => 'active',
        ]);

        $start = now()->addDays(5)->format('Y-m-d');
        $end = now()->addDays(6)->format('Y-m-d');

        app(AvailabilityService::class)->createBlock(
            $unitFirst,
            Carbon::parse($start)->startOfDay(),
            Carbon::parse($end)->endOfDay(),
            'test-block',
        );

        $response = $this->postTenantJson($host, '/booking/draft', [
            'motorcycle_id' => $m->id,
            'start_date' => $start,
            'end_date' => $end,
            'addons' => [],
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $this->assertSame($unitSecond->id, session('booking_draft.rental_unit_id'));
    }

    public function test_calculate_auto_skips_busy_first_unit(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $tenant = $this->createTenantWithActiveDomain('pubacc_calc_auto');
        $host = $this->tenancyHostForSlug('pubacc_calc_auto');

        $m = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Fleet Calc',
            'slug' => 'fleet-calc',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 3000,
            'uses_fleet_units' => true,
        ]);

        $unitFirst = RentalUnit::query()->create([
            'tenant_id' => $tenant->id,
            'motorcycle_id' => $m->id,
            'status' => 'active',
        ]);
        $unitSecond = RentalUnit::query()->create([
            'tenant_id' => $tenant->id,
            'motorcycle_id' => $m->id,
            'status' => 'active',
        ]);

        $start = now()->addDays(5)->format('Y-m-d');
        $end = now()->addDays(6)->format('Y-m-d');

        app(AvailabilityService::class)->createBlock(
            $unitFirst,
            Carbon::parse($start)->startOfDay(),
            Carbon::parse($end)->endOfDay(),
            'test-block',
        );

        $response = $this->postTenantJson($host, '/booking/calculate', [
            'motorcycle_id' => $m->id,
            'rental_unit_id' => null,
            'start_date' => $start,
            'end_date' => $end,
            'addons' => [],
        ]);

        $response->assertOk();
        $response->assertJsonPath('available', true);
        $response->assertJsonPath('rental_unit_id', $unitSecond->id);
    }

    public function test_checkout_redirects_when_draft_motorcycle_becomes_ineligible(): void
    {
        $tenant = $this->createTenantWithActiveDomain('pubacc_checkout_block');
        $host = $this->tenancyHostForSlug('pubacc_checkout_block');

        $m = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Later Hidden',
            'slug' => 'later-hidden',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 3000,
            'uses_fleet_units' => false,
        ]);

        $start = now()->addDays(5)->format('Y-m-d');
        $end = now()->addDays(6)->format('Y-m-d');

        $m->forceFill(['show_in_catalog' => false])->save();

        $response = $this->withSession([
            'booking_draft' => [
                'motorcycle_id' => $m->id,
                'rental_unit_id' => null,
                'start_date' => $start,
                'end_date' => $end,
                'addons' => [],
            ],
        ])->call('GET', 'http://'.$host.'/checkout');

        $response->assertRedirect(route('booking.index'));
    }

    public function test_checkout_redirects_when_fleet_draft_missing_rental_unit_id(): void
    {
        $tenant = $this->createTenantWithActiveDomain('pubacc_chk_fleet_null');
        $host = $this->tenancyHostForSlug('pubacc_chk_fleet_null');

        $m = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Fleet No Unit Draft',
            'slug' => 'fleet-draft-bad',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 3000,
            'uses_fleet_units' => true,
        ]);

        RentalUnit::query()->create([
            'tenant_id' => $tenant->id,
            'motorcycle_id' => $m->id,
            'status' => 'active',
        ]);

        $start = now()->addDays(5)->format('Y-m-d');
        $end = now()->addDays(6)->format('Y-m-d');

        $response = $this->withSession([
            'booking_draft' => [
                'motorcycle_id' => $m->id,
                'rental_unit_id' => null,
                'start_date' => $start,
                'end_date' => $end,
                'addons' => [],
            ],
        ])->call('GET', 'http://'.$host.'/checkout');

        $response->assertRedirect(route('booking.index'));
    }

    public function test_create_public_booking_throws_when_motorcycle_not_allowed(): void
    {
        $tenant = $this->createTenantWithActiveDomain('pubacc_svc_block');
        $m = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Archived',
            'slug' => 'archived-bike',
            'status' => 'archived',
            'show_in_catalog' => true,
            'price_per_day' => 3000,
            'uses_fleet_units' => false,
        ]);

        $start = now()->addDays(5)->format('Y-m-d');
        $end = now()->addDays(6)->format('Y-m-d');
        $startAt = Carbon::parse($start)->startOfDay();
        $endAt = Carbon::parse($end)->endOfDay();

        $this->expectException(\InvalidArgumentException::class);

        app(BookingService::class)->createPublicBooking([
            'tenant_id' => $tenant->id,
            'motorcycle_id' => $m->id,
            'rental_unit_id' => null,
            'start_date' => $start,
            'end_date' => $end,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'customer_name' => 'Test',
            'phone' => '+79991112233',
            'addons' => [],
        ]);
    }

    public function test_create_public_booking_throws_when_fleet_motorcycle_missing_rental_unit_id(): void
    {
        $tenant = $this->createTenantWithActiveDomain('pubacc_svc_fleet');
        $m = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Fleet',
            'slug' => 'fleet-only',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 3000,
            'uses_fleet_units' => true,
        ]);

        RentalUnit::query()->create([
            'tenant_id' => $tenant->id,
            'motorcycle_id' => $m->id,
            'status' => 'active',
        ]);

        $start = now()->addDays(5)->format('Y-m-d');
        $end = now()->addDays(6)->format('Y-m-d');
        $startAt = Carbon::parse($start)->startOfDay();
        $endAt = Carbon::parse($end)->endOfDay();

        $this->expectException(\InvalidArgumentException::class);

        app(BookingService::class)->createPublicBooking([
            'tenant_id' => $tenant->id,
            'motorcycle_id' => $m->id,
            'rental_unit_id' => null,
            'start_date' => $start,
            'end_date' => $end,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'customer_name' => 'Test',
            'phone' => '+79991112233',
            'addons' => [],
        ]);
    }

    public function test_create_public_booking_rejects_inactive_rental_unit(): void
    {
        $tenant = $this->createTenantWithActiveDomain('pubacc_svc_inactive_unit');
        $m = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Fleet Inactive',
            'slug' => 'fleet-inactive',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 3000,
            'uses_fleet_units' => true,
        ]);

        $inactive = RentalUnit::query()->create([
            'tenant_id' => $tenant->id,
            'motorcycle_id' => $m->id,
            'status' => 'maintenance',
        ]);

        $start = now()->addDays(5)->format('Y-m-d');
        $end = now()->addDays(6)->format('Y-m-d');
        $startAt = Carbon::parse($start)->startOfDay();
        $endAt = Carbon::parse($end)->endOfDay();

        $this->expectException(\InvalidArgumentException::class);

        app(BookingService::class)->createPublicBooking([
            'tenant_id' => $tenant->id,
            'motorcycle_id' => $m->id,
            'rental_unit_id' => $inactive->id,
            'start_date' => $start,
            'end_date' => $end,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'customer_name' => 'Test',
            'phone' => '+79991112233',
            'addons' => [],
        ]);
    }

    public function test_create_public_booking_rejects_unknown_public_catalog_location_id(): void
    {
        $tenant = $this->createTenantWithActiveDomain('pubacc_bad_catalog_loc');
        $m = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Fleet Loc',
            'slug' => 'fleet-loc',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 3000,
            'uses_fleet_units' => true,
        ]);
        $unit = RentalUnit::query()->create([
            'tenant_id' => $tenant->id,
            'motorcycle_id' => $m->id,
            'status' => 'active',
        ]);

        $start = now()->addDays(5)->format('Y-m-d');
        $end = now()->addDays(6)->format('Y-m-d');
        $startAt = Carbon::parse($start)->startOfDay();
        $endAt = Carbon::parse($end)->endOfDay();

        $this->expectException(\InvalidArgumentException::class);

        app(BookingService::class)->createPublicBooking([
            'tenant_id' => $tenant->id,
            'motorcycle_id' => $m->id,
            'rental_unit_id' => $unit->id,
            'public_catalog_location_id' => 999_999_999,
            'start_date' => $start,
            'end_date' => $end,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'customer_name' => 'Test',
            'phone' => '+79991112233',
            'addons' => [],
        ]);
    }

    public function test_create_public_booking_does_not_persist_inactive_addons(): void
    {
        $tenant = $this->createTenantWithActiveDomain('pubacc_inactive_addon');
        $m = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'With Addon',
            'slug' => 'with-addon-svc',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 3000,
            'uses_fleet_units' => false,
        ]);
        $inactive = Addon::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Off',
            'type' => 'optional',
            'price' => 500,
            'is_active' => false,
            'sort_order' => 0,
        ]);

        $start = now()->addDays(5)->format('Y-m-d');
        $end = now()->addDays(6)->format('Y-m-d');
        $startAt = Carbon::parse($start)->startOfDay();
        $endAt = Carbon::parse($end)->endOfDay();

        $booking = app(BookingService::class)->createPublicBooking([
            'tenant_id' => $tenant->id,
            'motorcycle_id' => $m->id,
            'rental_unit_id' => null,
            'start_date' => $start,
            'end_date' => $end,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'customer_name' => 'Test',
            'phone' => '+79991112233',
            'addons' => [(string) $inactive->id => 1],
        ]);

        $this->assertSame(0, $booking->addons()->count());
    }
}
