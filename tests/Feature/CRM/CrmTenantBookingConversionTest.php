<?php

namespace Tests\Feature\CRM;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\CrmRequest;
use App\Models\Lead;
use App\Models\Motorcycle;
use App\Models\User;
use App\Product\CRM\CrmRequestOperatorService;
use App\Tenant\CurrentTenant;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

class CrmTenantBookingConversionTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function tearDown(): void
    {
        Filament::setCurrentPanel(null);
        parent::tearDown();
    }

    public function test_converted_tenant_booking_creates_confirmed_booking_from_lead(): void
    {
        $tenant = $this->createTenantWithActiveDomain('crm_book');
        $domain = $tenant->domains()->where('is_primary', true)->first();
        $host = $this->tenancyHostForSlug('crm_book');

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'operator', 'status' => 'active']);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->app->instance(
            CurrentTenant::class,
            new CurrentTenant($tenant, $domain, false, $host),
        );

        $m = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'M X',
            'slug' => 'm-x',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 1000,
        ]);

        $crm = $this->makeCrmRequest($tenant->id, [
            'request_type' => 'tenant_booking',
            'name' => 'Client',
            'phone' => '+79991112233',
        ]);

        Lead::query()->create([
            'tenant_id' => $tenant->id,
            'crm_request_id' => $crm->id,
            'name' => 'Client',
            'phone' => '+79991112233',
            'motorcycle_id' => $m->id,
            'rental_date_from' => '2026-04-01',
            'rental_date_to' => '2026-04-04',
            'source' => 'booking_form',
            'status' => 'new',
        ]);

        $svc = app(CrmRequestOperatorService::class);
        $svc->changeStatus($user, $crm, CrmRequest::STATUS_CONVERTED);

        $booking = Booking::query()->where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($booking);
        $this->assertSame(BookingStatus::CONFIRMED, $booking->status);
        $this->assertSame('2026-04-01', $booking->start_date->toDateString());
        $this->assertSame('2026-04-04', $booking->end_date->toDateString());
        $this->assertSame($m->id, $booking->motorcycle_id);
        $this->assertSame('crm_converted', $booking->source);
    }

    public function test_second_transition_to_converted_does_not_duplicate_booking(): void
    {
        $tenant = $this->createTenantWithActiveDomain('crm_book2');
        $domain = $tenant->domains()->where('is_primary', true)->first();
        $host = $this->tenancyHostForSlug('crm_book2');

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'operator', 'status' => 'active']);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->app->instance(
            CurrentTenant::class,
            new CurrentTenant($tenant, $domain, false, $host),
        );

        $m = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'M Y',
            'slug' => 'm-y',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 500,
        ]);

        $crm = $this->makeCrmRequest($tenant->id, [
            'request_type' => 'tenant_booking',
        ]);

        Lead::query()->create([
            'tenant_id' => $tenant->id,
            'crm_request_id' => $crm->id,
            'name' => 'T',
            'phone' => '+79990000000',
            'motorcycle_id' => $m->id,
            'rental_date_from' => '2026-05-01',
            'rental_date_to' => '2026-05-03',
            'source' => 'booking_form',
            'status' => 'new',
        ]);

        $svc = app(CrmRequestOperatorService::class);
        $svc->changeStatus($user, $crm, CrmRequest::STATUS_CONVERTED);
        $this->assertSame(1, Booking::query()->where('tenant_id', $tenant->id)->count());

        $crm->refresh();
        $svc->changeStatus($user, $crm, CrmRequest::STATUS_QUALIFIED);
        $crm->refresh();
        $svc->changeStatus($user, $crm, CrmRequest::STATUS_CONVERTED);

        $this->assertSame(1, Booking::query()->where('tenant_id', $tenant->id)->count());
    }

    public function test_lead_status_confirmed_creates_booking_without_crm_converted(): void
    {
        $tenant = $this->createTenantWithActiveDomain('lead_conf_book');

        $m = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'M Z',
            'slug' => 'm-z',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 800,
        ]);

        $lead = Lead::query()->create([
            'tenant_id' => $tenant->id,
            'crm_request_id' => null,
            'name' => 'Клиент',
            'phone' => '+79997776655',
            'motorcycle_id' => $m->id,
            'rental_date_from' => '2026-07-01',
            'rental_date_to' => '2026-07-05',
            'source' => 'booking_form',
            'status' => 'new',
        ]);

        $this->assertNull(Booking::query()->where('lead_id', $lead->id)->first());

        $lead->update(['status' => 'confirmed']);

        $booking = Booking::query()->where('lead_id', $lead->id)->first();
        $this->assertNotNull($booking);
        $this->assertSame(BookingStatus::CONFIRMED, $booking->status);
        $this->assertSame('lead_confirmed', $booking->source);
        $this->assertSame('2026-07-01', $booking->start_date->toDateString());
        $this->assertSame('2026-07-05', $booking->end_date->toDateString());
    }
}
