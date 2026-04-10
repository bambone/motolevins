<?php

namespace Tests\Unit\Filament;

use App\Filament\Tenant\Forms\ManualOperatorBookingForm;
use App\Models\Lead;
use App\Models\Motorcycle;
use App\Models\RentalUnit;
use App\Models\Tenant;
use App\Tenant\CurrentTenant;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

class ManualOperatorBookingFormLeadDefaultsTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Filament::setCurrentPanel(null);
        parent::tearDown();
    }

    private function bindTenantContext(Tenant $tenant): void
    {
        $domain = $tenant->domains()->where('is_primary', true)->first();
        $host = $domain !== null ? (string) $domain->host : 'localhost';
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->app->instance(
            CurrentTenant::class,
            new CurrentTenant($tenant, $domain, false, $host),
        );
    }

    public function test_booking_from_lead_defaults_include_motorcycle_dates_and_first_active_unit(): void
    {
        $tenant = $this->createTenantWithActiveDomain('mobl_defaults');
        $this->bindTenantContext($tenant);

        $m = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Honda Test',
            'slug' => 'honda-test',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 500,
        ]);
        $unit = RentalUnit::query()->create([
            'tenant_id' => $tenant->id,
            'motorcycle_id' => $m->id,
            'status' => 'active',
        ]);

        $lead = Lead::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Клиент',
            'phone' => '+79991112233',
            'motorcycle_id' => $m->id,
            'rental_date_from' => '2026-04-10',
            'rental_date_to' => '2026-04-15',
            'source' => 'booking_form',
            'status' => 'new',
        ]);

        $defaults = ManualOperatorBookingForm::bookingFromLeadFormDefaults($lead);

        $this->assertSame($m->id, $defaults['motorcycle_id']);
        $this->assertSame($unit->id, $defaults['rental_unit_id']);
        $this->assertNotNull($defaults['start_date']);
        $this->assertNotNull($defaults['end_date']);
        $this->assertSame('2026-04-10', $defaults['start_date']->format('Y-m-d'));
        $this->assertSame('2026-04-15', $defaults['end_date']->format('Y-m-d'));
    }

    public function test_booking_from_lead_defaults_omit_unit_when_none_active(): void
    {
        $tenant = $this->createTenantWithActiveDomain('mobl_no_unit');
        $this->bindTenantContext($tenant);

        $m = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Yamaha Test',
            'slug' => 'yamaha-test',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 600,
        ]);

        $lead = Lead::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Клиент',
            'phone' => '+79991112233',
            'motorcycle_id' => $m->id,
            'rental_date_from' => '2026-05-01',
            'rental_date_to' => '2026-05-03',
            'source' => 'booking_form',
            'status' => 'new',
        ]);

        $defaults = ManualOperatorBookingForm::bookingFromLeadFormDefaults($lead);

        $this->assertSame($m->id, $defaults['motorcycle_id']);
        $this->assertArrayNotHasKey('rental_unit_id', $defaults);
    }
}
