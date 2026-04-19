<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\Motorcycle;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

final class TenantMotorcycleQuoteApiTest extends TestCase
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

    public function test_quote_returns_ok_for_legacy_priced_motorcycle(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        config(['tenancy.central_domains' => ['localhost', '127.0.0.1']]);
        config(['tenancy.root_domain' => 'test']);

        $tenant = $this->createTenantWithActiveDomain('motoquote');
        $bike = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Quote Bike',
            'slug' => 'quote-bike',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 4000,
        ]);

        $host = $this->tenancyHostForSlug('motoquote');
        $response = $this->postTenantJson($host, '/api/tenant/motorcycles/quote', [
            'motorcycle_id' => $bike->id,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-03',
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', 'ok');
        // 4000 RUB/day × 100 kopeks × 3 days
        $this->assertSame(1_200_000, (int) $response->json('totals.rental_total_minor'));
    }
}
