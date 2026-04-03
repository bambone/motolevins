<?php

namespace Tests\Feature\Bookings;

use App\Enums\BookingStatus;
use App\Filament\Tenant\Pages\BookingCalendarPage;
use App\Models\Booking;
use App\Models\Lead;
use App\Models\Motorcycle;
use App\Models\Tenant;
use App\Models\User;
use App\Tenant\CurrentTenant;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

class BookingCalendarTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    private function bindTenantFilamentContext(Tenant $tenant): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $domain = $tenant->domains()->where('is_primary', true)->first();
        $this->app->instance(
            CurrentTenant::class,
            new CurrentTenant($tenant, $domain, false, $this->tenancyHostForSlug((string) $tenant->slug))
        );
    }

    private function bookingManager(Tenant $tenant): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'booking_manager', 'status' => 'active']);

        return $user;
    }

    #[Test]
    public function operator_without_manage_bookings_cannot_mount_calendar_page(): void
    {
        $tenant = $this->createTenantWithActiveDomain('bcal_op');
        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'operator', 'status' => 'active']);
        $this->bindTenantFilamentContext($tenant);

        Livewire::actingAs($user)
            ->test(BookingCalendarPage::class)
            ->assertForbidden();

        Filament::setCurrentPanel(null);
    }

    #[Test]
    public function fetch_events_returns_only_current_tenant_bookings(): void
    {
        $ta = $this->createTenantWithActiveDomain('bcal_a');
        $tb = $this->createTenantWithActiveDomain('bcal_b');
        $mA = Motorcycle::query()->create([
            'tenant_id' => $ta->id,
            'name' => 'M A',
            'slug' => 'm-a',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 1000,
        ]);
        $mB = Motorcycle::query()->create([
            'tenant_id' => $tb->id,
            'name' => 'M B',
            'slug' => 'm-b',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 1000,
        ]);

        Booking::query()->create([
            'tenant_id' => $ta->id,
            'motorcycle_id' => $mA->id,
            'rental_unit_id' => null,
            'bike_id' => null,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-03',
            'start_at' => null,
            'end_at' => null,
            'status' => BookingStatus::CONFIRMED,
            'price_per_day_snapshot' => 1000,
            'total_price' => 3000,
            'customer_name' => 'ClientA',
            'phone' => '+79991111111',
            'source' => 'test',
        ]);
        Booking::query()->create([
            'tenant_id' => $tb->id,
            'motorcycle_id' => $mB->id,
            'rental_unit_id' => null,
            'bike_id' => null,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-03',
            'start_at' => null,
            'end_at' => null,
            'status' => BookingStatus::CONFIRMED,
            'price_per_day_snapshot' => 1000,
            'total_price' => 3000,
            'customer_name' => 'ClientB',
            'phone' => '+79992222222',
            'source' => 'test',
        ]);

        $user = $this->bookingManager($ta);
        $this->bindTenantFilamentContext($ta);

        $events = Livewire::actingAs($user)
            ->test(BookingCalendarPage::class)
            ->instance()
            ->fetchEvents('2026-06-28T00:00:00+00:00', '2026-07-10T00:00:00+00:00');

        $this->assertCount(1, $events);
        $this->assertStringContainsString('ClientA', $events[0]['title']);
        $this->assertStringNotContainsString('ClientB', $events[0]['title']);

        Filament::setCurrentPanel(null);
    }

    #[Test]
    public function fetch_events_excludes_non_occupying_status(): void
    {
        $tenant = $this->createTenantWithActiveDomain('bcal_stat');
        $m = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'M',
            'slug' => 'm-stat',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 1000,
        ]);

        Booking::query()->create([
            'tenant_id' => $tenant->id,
            'motorcycle_id' => $m->id,
            'rental_unit_id' => null,
            'bike_id' => null,
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-03',
            'start_at' => null,
            'end_at' => null,
            'status' => BookingStatus::CANCELLED,
            'price_per_day_snapshot' => 1000,
            'total_price' => 3000,
            'customer_name' => 'X',
            'phone' => '+79993333333',
            'source' => 'test',
        ]);

        $user = $this->bookingManager($tenant);
        $this->bindTenantFilamentContext($tenant);

        $out = Livewire::actingAs($user)
            ->test(BookingCalendarPage::class)
            ->instance()
            ->fetchEvents('2026-07-28T00:00:00+00:00', '2026-08-10T00:00:00+00:00');
        $this->assertCount(0, $out);

        Filament::setCurrentPanel(null);
    }

    #[Test]
    public function fetch_events_includes_crm_url_when_lead_chain_valid(): void
    {
        $tenant = $this->createTenantWithActiveDomain('bcal_crm');
        $m = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'M CRM',
            'slug' => 'm-crm',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 1000,
        ]);
        $crm = $this->makeCrmRequest($tenant->id, [
            'email' => 'crm-cal-'.uniqid('', true).'@example.test',
            'request_type' => 'tenant_booking',
        ]);
        $lead = Lead::query()->create([
            'tenant_id' => $tenant->id,
            'crm_request_id' => $crm->id,
            'name' => 'Lead Name',
            'phone' => '+79995555555',
            'motorcycle_id' => $m->id,
            'status' => 'new',
            'source' => 'test',
        ]);

        Booking::query()->create([
            'tenant_id' => $tenant->id,
            'motorcycle_id' => $m->id,
            'lead_id' => $lead->id,
            'rental_unit_id' => null,
            'bike_id' => null,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-03',
            'start_at' => null,
            'end_at' => null,
            'status' => BookingStatus::CONFIRMED,
            'price_per_day_snapshot' => 1000,
            'total_price' => 3000,
            'customer_name' => 'From booking',
            'phone' => '+79996666666',
            'source' => 'test',
        ]);

        $user = $this->bookingManager($tenant);
        $this->bindTenantFilamentContext($tenant);

        $component = Livewire::actingAs($user)->test(BookingCalendarPage::class);
        $events = $component->instance()->fetchEvents('2026-08-28T00:00:00+00:00', '2026-09-10T00:00:00+00:00');
        $this->assertCount(1, $events);
        $this->assertNotEmpty($events[0]['extendedProps']['urls']['crm'] ?? null);

        Filament::setCurrentPanel(null);
    }

    #[Test]
    public function highlight_booking_id_sets_extended_prop_when_in_range(): void
    {
        $tenant = $this->createTenantWithActiveDomain('bcal_hi');
        $m = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'M Hi',
            'slug' => 'm-hi',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 1000,
        ]);
        $booking = Booking::query()->create([
            'tenant_id' => $tenant->id,
            'motorcycle_id' => $m->id,
            'rental_unit_id' => null,
            'bike_id' => null,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-03',
            'start_at' => null,
            'end_at' => null,
            'status' => BookingStatus::CONFIRMED,
            'price_per_day_snapshot' => 1000,
            'total_price' => 3000,
            'customer_name' => 'H',
            'phone' => '+79997777777',
            'source' => 'test',
        ]);

        $user = $this->bookingManager($tenant);
        $this->bindTenantFilamentContext($tenant);

        $component = Livewire::actingAs($user)->test(BookingCalendarPage::class, [
            'booking_id' => $booking->id,
        ]);
        $events = $component->instance()->fetchEvents('2026-09-28T00:00:00+00:00', '2026-10-10T00:00:00+00:00');
        $this->assertTrue($events[0]['extendedProps']['highlighted'] ?? false);

        Filament::setCurrentPanel(null);
    }

    #[Test]
    public function highlight_foreign_booking_id_does_not_leak(): void
    {
        $ta = $this->createTenantWithActiveDomain('bcal_hi_a');
        $tb = $this->createTenantWithActiveDomain('bcal_hi_b');
        $m = Motorcycle::query()->create([
            'tenant_id' => $tb->id,
            'name' => 'M other',
            'slug' => 'm-o',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 1000,
        ]);
        $foreign = Booking::query()->create([
            'tenant_id' => $tb->id,
            'motorcycle_id' => $m->id,
            'rental_unit_id' => null,
            'bike_id' => null,
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-03',
            'start_at' => null,
            'end_at' => null,
            'status' => BookingStatus::CONFIRMED,
            'price_per_day_snapshot' => 1000,
            'total_price' => 3000,
            'customer_name' => 'Other',
            'phone' => '+79998888888',
            'source' => 'test',
        ]);

        $user = $this->bookingManager($ta);
        $this->bindTenantFilamentContext($ta);

        $component = Livewire::actingAs($user)->test(BookingCalendarPage::class, [
            'booking_id' => $foreign->id,
        ]);
        $events = $component->instance()->fetchEvents('2026-10-28T00:00:00+00:00', '2026-11-10T00:00:00+00:00');
        $this->assertCount(0, $events);

        Filament::setCurrentPanel(null);
    }

    #[Test]
    public function fetch_events_uses_date_span_when_timestamps_do_not_match_date_columns(): void
    {
        $tenant = $this->createTenantWithActiveDomain('bcal_mismatch');
        $m = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'M mis',
            'slug' => 'm-mis',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 1000,
        ]);

        // Как в части реальных данных: даты периода заполнены, а start_at/end_at указывают только на первый день.
        Booking::query()->create([
            'tenant_id' => $tenant->id,
            'motorcycle_id' => $m->id,
            'rental_unit_id' => null,
            'bike_id' => null,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-03',
            'start_at' => '2026-07-01 00:00:00',
            'end_at' => '2026-07-01 23:59:59',
            'status' => BookingStatus::CONFIRMED,
            'price_per_day_snapshot' => 1000,
            'total_price' => 3000,
            'customer_name' => 'SpanFix',
            'phone' => '+79991231212',
            'source' => 'test',
        ]);

        $user = $this->bookingManager($tenant);
        $this->bindTenantFilamentContext($tenant);

        $events = Livewire::actingAs($user)
            ->test(BookingCalendarPage::class)
            ->instance()
            ->fetchEvents('2026-06-28T00:00:00+00:00', '2026-07-10T00:00:00+00:00');

        $this->assertCount(1, $events);
        $this->assertTrue($events[0]['allDay']);
        $this->assertSame('2026-07-01', $events[0]['start']);
        $this->assertSame('2026-07-04', $events[0]['end']);
        $this->assertStringContainsString('SpanFix', $events[0]['title']);

        Filament::setCurrentPanel(null);
    }

    #[Test]
    public function calendar_page_loads_on_tenant_host(): void
    {
        $tenant = $this->createTenantWithActiveDomain('bcal_http');
        $user = $this->bookingManager($tenant);

        $path = '/admin/bookings/calendar?view=week&date=2026-04-02&motorcycle_id=999';
        $response = $this->actingAs($user)->call('GET', 'http://'.$this->tenancyHostForSlug('bcal_http').$path);

        $response->assertOk();
    }
}
