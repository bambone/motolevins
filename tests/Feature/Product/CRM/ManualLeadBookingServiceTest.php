<?php

namespace Tests\Feature\Product\CRM;

use App\Enums\BookingStatus;
use App\Models\Lead;
use App\Models\Motorcycle;
use App\Models\RentalUnit;
use App\Models\Tenant;
use App\Models\User;
use App\Product\CRM\DTO\ManualBookingCreateData;
use App\Product\CRM\DTO\ManualLeadCreateData;
use App\Product\CRM\ManualLeadBookingService;
use App\Tenant\CurrentTenant;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

class ManualLeadBookingServiceTest extends TestCase
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

    private function bindTenantContext(Tenant $tenant): void
    {
        $domain = $tenant->domains()->where('is_primary', true)->first();
        $host = $domain->host;
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->app->instance(
            CurrentTenant::class,
            new CurrentTenant($tenant, $domain, false, $host),
        );
    }

    /**
     * @return array{0: Tenant, 1: Motorcycle, 2: RentalUnit}
     */
    private function tenantWithActiveUnit(string $slug): array
    {
        $tenant = $this->createTenantWithActiveDomain($slug);
        $m = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Bike '.$slug,
            'slug' => 'bike-'.$slug,
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 1000,
        ]);
        $unit = RentalUnit::query()->create([
            'tenant_id' => $tenant->id,
            'motorcycle_id' => $m->id,
            'status' => 'active',
        ]);

        return [$tenant, $m, $unit];
    }

    public function test_creates_lead_only_without_crm(): void
    {
        [$tenant] = $this->tenantWithActiveUnit('mlb_lead_only');
        $this->bindTenantContext($tenant);

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'operator', 'status' => 'active']);

        $this->actingAs($user);

        $svc = app(ManualLeadBookingService::class);
        $result = $svc->createManualLead(new ManualLeadCreateData(
            tenantId: $tenant->id,
            name: 'Иван',
            phone: '+79991112233',
            createCrm: false,
            createBooking: false,
        ));

        $this->assertNull($result->crmRequest);
        $this->assertNull($result->booking);
        $this->assertNotNull($result->lead);
        $this->assertSame('in_progress', $result->lead->status);
        $this->assertSame('manual', $result->lead->source);
        $this->assertNull($result->lead->crm_request_id);
    }

    public function test_creates_lead_with_crm_tenant_operator(): void
    {
        [$tenant] = $this->tenantWithActiveUnit('mlb_crm');
        $this->bindTenantContext($tenant);

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'operator', 'status' => 'active']);

        $this->actingAs($user);

        $svc = app(ManualLeadBookingService::class);
        $result = $svc->createManualLead(new ManualLeadCreateData(
            tenantId: $tenant->id,
            name: 'Пётр',
            phone: '+79992223344',
            comment: 'Звонил',
            createCrm: true,
            createBooking: false,
        ));

        $this->assertNotNull($result->crmRequest);
        $this->assertSame(ManualLeadBookingService::REQUEST_TYPE_TENANT_OPERATOR, $result->crmRequest->request_type);
        $this->assertSame('manual', $result->crmRequest->source);
        $this->assertSame('phone', $result->crmRequest->channel);
        $this->assertNotNull($result->lead);
        $this->assertSame('in_progress', $result->lead->status);
        $this->assertSame($result->crmRequest->id, $result->lead->crm_request_id);
    }

    public function test_creates_lead_with_crm_and_booking_in_one_transaction(): void
    {
        [$tenant, $m, $unit] = $this->tenantWithActiveUnit('mlb_crm_book');
        $this->bindTenantContext($tenant);

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'booking_manager', 'status' => 'active']);

        $this->actingAs($user);

        $result = app(ManualLeadBookingService::class)->createManualLead(new ManualLeadCreateData(
            tenantId: $tenant->id,
            name: 'CRM+бронь',
            phone: '+79990001122',
            createCrm: true,
            createBooking: true,
            motorcycleId: $m->id,
            rentalUnitId: $unit->id,
            rentalDateFromYmd: '2026-12-01',
            rentalDateToYmd: '2026-12-03',
        ));

        $this->assertNotNull($result->crmRequest);
        $this->assertNotNull($result->lead);
        $this->assertNotNull($result->booking);
        $this->assertSame($result->lead->id, $result->booking->lead_id);
        $this->assertSame(BookingStatus::CONFIRMED, $result->booking->status);
    }

    public function test_creates_lead_with_booking_when_user_has_manage_bookings(): void
    {
        [$tenant, $m, $unit] = $this->tenantWithActiveUnit('mlb_book');
        $this->bindTenantContext($tenant);

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'booking_manager', 'status' => 'active']);

        $this->actingAs($user);

        $svc = app(ManualLeadBookingService::class);
        $result = $svc->createManualLead(new ManualLeadCreateData(
            tenantId: $tenant->id,
            name: 'Клиент',
            phone: '+79993334455',
            createCrm: false,
            createBooking: true,
            motorcycleId: $m->id,
            rentalUnitId: $unit->id,
            rentalDateFromYmd: '2026-06-10',
            rentalDateToYmd: '2026-06-12',
        ));

        $this->assertNotNull($result->lead);
        $this->assertNotNull($result->booking);
        $this->assertSame(BookingStatus::CONFIRMED, $result->booking->status);
        $this->assertSame($result->lead->id, $result->booking->lead_id);
        $this->assertSame('manual', $result->booking->source);
        $this->assertSame($unit->id, $result->booking->rental_unit_id);
    }

    public function test_booking_conflict_throws_validation_exception(): void
    {
        [$tenant, $m, $unit] = $this->tenantWithActiveUnit('mlb_conflict');
        $this->bindTenantContext($tenant);

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'booking_manager', 'status' => 'active']);

        $this->actingAs($user);

        $svc = app(ManualLeadBookingService::class);
        $svc->createManualLead(new ManualLeadCreateData(
            tenantId: $tenant->id,
            name: 'Первый',
            phone: '+79994445566',
            createCrm: false,
            createBooking: true,
            motorcycleId: $m->id,
            rentalUnitId: $unit->id,
            rentalDateFromYmd: '2026-07-01',
            rentalDateToYmd: '2026-07-03',
        ));

        $this->expectException(ValidationException::class);
        $svc->createManualLead(new ManualLeadCreateData(
            tenantId: $tenant->id,
            name: 'Второй',
            phone: '+79995556677',
            createCrm: false,
            createBooking: true,
            motorcycleId: $m->id,
            rentalUnitId: $unit->id,
            rentalDateFromYmd: '2026-07-02',
            rentalDateToYmd: '2026-07-04',
        ));
    }

    public function test_creates_booking_for_existing_lead(): void
    {
        [$tenant, $m, $unit] = $this->tenantWithActiveUnit('mlb_exist');
        $this->bindTenantContext($tenant);

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'booking_manager', 'status' => 'active']);

        $this->actingAs($user);

        $lead = Lead::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Старый',
            'phone' => '+79996667788',
            'source' => 'phone',
            'status' => 'in_progress',
        ]);

        $svc = app(ManualLeadBookingService::class);
        $result = $svc->createManualBooking(new ManualBookingCreateData(
            tenantId: $tenant->id,
            name: 'Старый',
            motorcycleId: $m->id,
            rentalUnitId: $unit->id,
            startDateYmd: '2026-08-01',
            endDateYmd: '2026-08-02',
            phone: '+79996667788',
            existingLeadId: $lead->id,
            createLead: false,
            createCrm: false,
        ));

        $this->assertSame($lead->id, $result->lead->id);
        $this->assertNotNull($result->booking);
        $this->assertSame($lead->id, $result->booking->lead_id);
    }

    public function test_operator_without_manage_bookings_cannot_create_booking(): void
    {
        [$tenant, $m, $unit] = $this->tenantWithActiveUnit('mlb_perm');
        $this->bindTenantContext($tenant);

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'operator', 'status' => 'active']);

        $this->actingAs($user);

        $this->expectException(AuthorizationException::class);
        app(ManualLeadBookingService::class)->createManualLead(new ManualLeadCreateData(
            tenantId: $tenant->id,
            name: 'X',
            createCrm: false,
            createBooking: true,
            motorcycleId: $m->id,
            rentalUnitId: $unit->id,
            rentalDateFromYmd: '2026-09-01',
            rentalDateToYmd: '2026-09-02',
        ));
    }

    public function test_tenant_isolation_rejects_foreign_rental_unit(): void
    {
        [$tenantA, $mA] = $this->tenantWithActiveUnit('mlb_ta');
        [, $mB, $unitB] = $this->tenantWithActiveUnit('mlb_tb');

        $this->bindTenantContext($tenantA);

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenantA->id, ['role' => 'booking_manager', 'status' => 'active']);

        $this->actingAs($user);

        $this->expectException(ValidationException::class);
        app(ManualLeadBookingService::class)->createManualLead(new ManualLeadCreateData(
            tenantId: $tenantA->id,
            name: 'Iso',
            phone: '+79998889900',
            createCrm: false,
            createBooking: true,
            motorcycleId: $mA->id,
            rentalUnitId: $unitB->id,
            rentalDateFromYmd: '2026-10-01',
            rentalDateToYmd: '2026-10-02',
        ));
    }

    public function test_manual_booking_without_crm_creates_lead_only(): void
    {
        [$tenant, $m, $unit] = $this->tenantWithActiveUnit('mlb_nocrm');
        $this->bindTenantContext($tenant);

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'booking_manager', 'status' => 'active']);

        $this->actingAs($user);

        $result = app(ManualLeadBookingService::class)->createManualBooking(new ManualBookingCreateData(
            tenantId: $tenant->id,
            name: 'Без CRM',
            motorcycleId: $m->id,
            rentalUnitId: $unit->id,
            startDateYmd: '2026-11-01',
            endDateYmd: '2026-11-02',
            phone: '+79997778899',
            createLead: true,
            createCrm: false,
        ));

        $this->assertNull($result->crmRequest);
        $this->assertNotNull($result->lead);
        $this->assertNull($result->lead->crm_request_id);
        $this->assertNotNull($result->booking);
    }

    public function test_duplicate_quick_repeat_same_manual_lead_payload_is_rejected(): void
    {
        [$tenant] = $this->tenantWithActiveUnit('mlb_idem');
        $this->bindTenantContext($tenant);

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'operator', 'status' => 'active']);

        $this->actingAs($user);

        $svc = app(ManualLeadBookingService::class);
        $payload = new ManualLeadCreateData(
            tenantId: $tenant->id,
            name: 'Повтор',
            phone: '+79991119999',
            createCrm: false,
            createBooking: false,
        );

        $svc->createManualLead($payload);

        $this->expectException(ValidationException::class);
        $svc->createManualLead($payload);
    }

    public function test_manual_lead_with_different_phone_after_idempotency_window_allowed(): void
    {
        [$tenant] = $this->tenantWithActiveUnit('mlb_idem2');
        $this->bindTenantContext($tenant);

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'operator', 'status' => 'active']);

        $this->actingAs($user);

        $svc = app(ManualLeadBookingService::class);
        $first = $svc->createManualLead(new ManualLeadCreateData(
            tenantId: $tenant->id,
            name: 'А',
            phone: '+79991110001',
            createCrm: false,
            createBooking: false,
        ));
        $second = $svc->createManualLead(new ManualLeadCreateData(
            tenantId: $tenant->id,
            name: 'Б',
            phone: '+79991110002',
            createCrm: false,
            createBooking: false,
        ));

        $this->assertNotSame($first->lead->id, $second->lead->id);
    }

    public function test_user_without_tenant_membership_cannot_create_lead(): void
    {
        [$tenant] = $this->tenantWithActiveUnit('mlb_nomem');
        $this->bindTenantContext($tenant);

        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($user);

        $this->expectException(AuthorizationException::class);
        app(ManualLeadBookingService::class)->createManualLead(new ManualLeadCreateData(
            tenantId: $tenant->id,
            name: 'Nope',
            createCrm: false,
        ));
    }
}
