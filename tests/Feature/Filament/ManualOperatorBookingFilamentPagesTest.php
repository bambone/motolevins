<?php

namespace Tests\Feature\Filament;

use App\Filament\Tenant\Pages\BookingCalendarPage;
use App\Filament\Tenant\Resources\BookingResource;
use App\Filament\Tenant\Resources\BookingResource\Pages\ListBookings;
use App\Filament\Tenant\Resources\LeadResource;
use App\Filament\Tenant\Resources\LeadResource\Pages\ListLeads;
use App\Models\Lead;
use App\Models\Motorcycle;
use App\Models\Tenant;
use App\Models\User;
use App\Tenant\CurrentTenant;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

/**
 * Ручной операторский флоу: страницы с действиями «Добавить обращение» / «Добавить бронирование» монтируются без ошибок.
 */
class ManualOperatorBookingFilamentPagesTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    protected function tearDown(): void
    {
        Filament::setCurrentPanel(null);
        parent::tearDown();
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

    public function test_list_leads_page_mounts_and_shows_add_lead_header_action(): void
    {
        $tenant = $this->createTenantWithActiveDomain('mop_leads');
        $user = $this->bookingManager($tenant);
        $this->bindTenantFilamentContext($tenant);

        $html = Livewire::actingAs($user)
            ->test(ListLeads::class)
            ->assertSuccessful()
            ->html();

        $this->assertStringContainsString('Добавить обращение', $html);
    }

    /**
     * Регрессия: схема модалки «Добавить обращение» должна монтироваться без TypeError
     * (в контексте Filament Actions передаётся {@see Get}, не Forms\Get).
     */
    public function test_manual_lead_header_action_modal_mounts_schema(): void
    {
        $tenant = $this->createTenantWithActiveDomain('mop_lead_modal');
        $user = $this->bookingManager($tenant);
        $this->bindTenantFilamentContext($tenant);

        Livewire::actingAs($user)
            ->test(ListLeads::class)
            ->mountAction('create_manual_lead')
            ->assertSuccessful();
    }

    public function test_manual_booking_header_action_on_calendar_mounts_schema(): void
    {
        $tenant = $this->createTenantWithActiveDomain('mop_cal_modal');
        $user = $this->bookingManager($tenant);
        $this->bindTenantFilamentContext($tenant);

        Livewire::actingAs($user)
            ->test(BookingCalendarPage::class)
            ->mountAction('create_manual_booking')
            ->assertSuccessful();
    }

    public function test_manual_booking_header_action_on_list_bookings_mounts_schema(): void
    {
        $tenant = $this->createTenantWithActiveDomain('mop_list_book_modal');
        $user = $this->bookingManager($tenant);
        $this->bindTenantFilamentContext($tenant);

        Livewire::actingAs($user)
            ->test(ListBookings::class)
            ->mountAction('create_manual_booking')
            ->assertSuccessful();
    }

    public function test_list_leads_page_with_row_shows_booking_record_action(): void
    {
        $tenant = $this->createTenantWithActiveDomain('mop_lead_row');
        $user = $this->bookingManager($tenant);

        $m = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Moto Row',
            'slug' => 'moto-row',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 500,
        ]);

        Lead::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Клиент для строки',
            'phone' => '+79991112233',
            'motorcycle_id' => $m->id,
            'source' => 'manual',
            'status' => 'in_progress',
        ]);

        $this->bindTenantFilamentContext($tenant);

        $html = Livewire::actingAs($user)
            ->test(ListLeads::class)
            ->assertSuccessful()
            ->html();

        $this->assertStringContainsString('Добавить обращение', $html);
        $this->assertStringContainsString('Клиент для строки', $html);
        $this->assertStringContainsString('Бронирование', $html);
    }

    public function test_list_bookings_page_mounts_and_shows_add_booking_header_action(): void
    {
        $tenant = $this->createTenantWithActiveDomain('mop_book_list');
        $user = $this->bookingManager($tenant);
        $this->bindTenantFilamentContext($tenant);

        $html = Livewire::actingAs($user)
            ->test(ListBookings::class)
            ->assertSuccessful()
            ->html();

        $this->assertStringContainsString('Добавить бронирование', $html);
    }

    public function test_booking_calendar_page_mounts_and_shows_add_booking_header_action(): void
    {
        $tenant = $this->createTenantWithActiveDomain('mop_cal');
        $user = $this->bookingManager($tenant);
        $this->bindTenantFilamentContext($tenant);

        $html = Livewire::actingAs($user)
            ->test(BookingCalendarPage::class)
            ->assertSuccessful()
            ->html();

        $this->assertStringContainsString('Добавить бронирование', $html);
    }

    public function test_list_leads_and_bookings_open_via_http_get_on_tenant_host(): void
    {
        $tenant = $this->createTenantWithActiveDomain('mop_http');
        $host = $this->tenancyHostForSlug('mop_http');
        $user = $this->bookingManager($tenant);
        $this->bindTenantFilamentContext($tenant);

        $leadsPath = parse_url(LeadResource::getUrl(null, [], false, 'admin'), PHP_URL_PATH);
        $bookingsPath = parse_url(BookingResource::getUrl(null, [], false, 'admin'), PHP_URL_PATH);
        $calendarPath = parse_url(BookingCalendarPage::getUrl([], false, 'admin'), PHP_URL_PATH);

        $this->assertIsString($leadsPath);
        $this->assertIsString($bookingsPath);
        $this->assertIsString($calendarPath);

        foreach ([
            ['Обращения', $leadsPath],
            ['Бронирования (список)', $bookingsPath],
            ['Календарь', $calendarPath],
        ] as [$label, $path]) {
            $response = $this->actingAs($user)->call('GET', 'http://'.$host.$path);
            $this->assertSame(200, $response->status(), $label.' '.$path);
            $this->assertFalse($response->isRedirect(), $label.' should not redirect');
            $this->assertStringContainsString('</html>', (string) $response->getContent(), $label);
        }
    }
}
