<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\Addon;
use App\Models\Motorcycle;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

/**
 * Регрессия: {@code addons} — map addonId =&gt; qty; валидация должна проверять ключи (ID), а не только значения.
 */
final class PublicBookingAddonsValidationTest extends TestCase
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

    private function makeAddon(int $tenantId, string $name = 'Extra'): Addon
    {
        return Addon::query()->create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'type' => 'optional',
            'price' => 500,
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }

    private function makeAvailableBike(int $tenantId): Motorcycle
    {
        return Motorcycle::query()->create([
            'tenant_id' => $tenantId,
            'name' => 'Addon Test Bike',
            'slug' => 'addon-test-bike',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 2000,
            'uses_fleet_units' => false,
        ]);
    }

    public function test_calculate_rejects_addon_belonging_to_another_tenant(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $tenantA = $this->createTenantWithActiveDomain('addon_val_a');
        $tenantB = $this->createTenantWithActiveDomain('addon_val_b');
        $hostA = $this->tenancyHostForSlug('addon_val_a');

        $bike = $this->makeAvailableBike($tenantA->id);
        $foreignAddon = $this->makeAddon($tenantB->id, 'Foreign');

        $start = now()->addDays(20)->format('Y-m-d');
        $end = now()->addDays(21)->format('Y-m-d');

        $response = $this->postTenantJson($hostA, '/booking/calculate', [
            'motorcycle_id' => $bike->id,
            'rental_unit_id' => null,
            'start_date' => $start,
            'end_date' => $end,
            'addons' => [(string) $foreignAddon->id => 1],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['addons']);
    }

    public function test_calculate_rejects_nonexistent_addon_id(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $tenant = $this->createTenantWithActiveDomain('addon_val_ne');
        $host = $this->tenancyHostForSlug('addon_val_ne');
        $bike = $this->makeAvailableBike($tenant->id);

        $start = now()->addDays(21)->format('Y-m-d');
        $end = now()->addDays(22)->format('Y-m-d');

        $response = $this->postTenantJson($host, '/booking/calculate', [
            'motorcycle_id' => $bike->id,
            'rental_unit_id' => null,
            'start_date' => $start,
            'end_date' => $end,
            'addons' => ['999999999' => 1],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['addons']);
    }

    public function test_store_draft_rejects_addon_belonging_to_another_tenant(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $tenantA = $this->createTenantWithActiveDomain('addon_draft_a');
        $tenantB = $this->createTenantWithActiveDomain('addon_draft_b');
        $hostA = $this->tenancyHostForSlug('addon_draft_a');

        $bike = $this->makeAvailableBike($tenantA->id);
        $foreignAddon = $this->makeAddon($tenantB->id, 'ForeignDraft');

        $start = now()->addDays(22)->format('Y-m-d');
        $end = now()->addDays(23)->format('Y-m-d');

        $response = $this->postTenantJson($hostA, '/booking/draft', [
            'motorcycle_id' => $bike->id,
            'rental_unit_id' => null,
            'start_date' => $start,
            'end_date' => $end,
            'addons' => [$foreignAddon->id => 1],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['addons']);
    }
}
