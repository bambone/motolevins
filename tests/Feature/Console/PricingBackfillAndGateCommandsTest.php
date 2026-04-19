<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Booking;
use App\Models\Motorcycle;
use App\MotorcyclePricing\MotorcyclePricingSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

final class PricingBackfillAndGateCommandsTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    public function test_booking_backfill_command_updates_snapshot_columns(): void
    {
        $tenant = $this->createTenantWithActiveDomain('pricingbf');
        $slug = 'test-bike-'.uniqid();
        $m = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Test bike',
            'slug' => $slug,
            'price_per_day' => 1000,
            'status' => 'available',
            'pricing_profile_json' => null,
            'pricing_profile_schema_version' => MotorcyclePricingSchema::PROFILE_VERSION,
        ]);

        $b = Booking::factory()->forTenant($tenant)->withMotorcycle($m)->create([
            'start_date' => '2026-01-10',
            'end_date' => '2026-01-12',
            'start_at' => '2026-01-10 00:00:00',
            'end_at' => '2026-01-12 23:59:59',
            'price_per_day_snapshot' => 1000,
            'total_price' => 3000,
            'pricing_snapshot_schema_version' => null,
            'pricing_snapshot_json' => null,
        ]);

        $this->artisan('booking:backfill-pricing-snapshots', [
            '--tenant' => (string) $tenant->id,
        ])->assertSuccessful();

        $b->refresh();
        $this->assertSame(MotorcyclePricingSchema::SNAPSHOT_VERSION, (int) $b->pricing_snapshot_schema_version);
        $this->assertIsArray($b->pricing_snapshot_json);
        $this->assertNotEmpty($b->pricing_snapshot_json);
    }

    public function test_cutover_acceptance_gate_runs(): void
    {
        $tenant = $this->createTenantWithActiveDomain('gatebf');
        Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Gate bike',
            'slug' => 'gate-bike-'.uniqid(),
            'price_per_day' => 1000,
            'status' => 'available',
            'pricing_profile_json' => null,
            'pricing_profile_schema_version' => MotorcyclePricingSchema::PROFILE_VERSION,
        ]);

        $this->artisan('pricing:cutover-acceptance-gate', [
            '--tenant' => (string) $tenant->id,
            '--allow-warnings' => true,
        ])->assertSuccessful();
    }
}
