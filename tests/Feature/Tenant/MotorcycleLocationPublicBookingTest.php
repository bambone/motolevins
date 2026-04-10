<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Enums\MotorcycleLocationMode;
use App\Models\Motorcycle;
use App\Models\TenantLocation;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

final class MotorcycleLocationPublicBookingTest extends TestCase
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

    public function test_booking_calculate_returns_422_when_motorcycle_not_available_at_query_location(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $tenant = $this->createTenantWithActiveDomain('locbook');
        $host = $this->tenancyHostForSlug('locbook');

        $locA = TenantLocation::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Point A',
            'slug' => 'point-a',
            'is_active' => true,
        ]);
        $locB = TenantLocation::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Point B',
            'slug' => 'point-b',
            'is_active' => true,
        ]);

        $bike = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Loc Bike',
            'slug' => 'loc-bike',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 3000,
            'uses_fleet_units' => false,
            'location_mode' => MotorcycleLocationMode::Selected,
        ]);
        $bike->tenantLocations()->sync([$locA->id]);

        $start = now()->addDays(5)->format('Y-m-d');
        $end = now()->addDays(6)->format('Y-m-d');

        $response = $this->postTenantJson($host, '/booking/calculate?location='.$locB->slug, [
            'motorcycle_id' => $bike->id,
            'rental_unit_id' => null,
            'start_date' => $start,
            'end_date' => $end,
            'addons' => [],
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('available', false);
    }
}
