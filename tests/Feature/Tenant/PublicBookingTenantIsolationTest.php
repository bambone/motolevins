<?php

namespace Tests\Feature\Tenant;

use App\Models\Booking;
use App\Models\Motorcycle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

/**
 * Регрессия: поиск бронирования по номеру на thank-you и загрузка черновика из сессии
 * не должны опираться только на условный global scope без явного tenant_id.
 */
class PublicBookingTenantIsolationTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_thank_you_page_by_number_does_not_show_booking_from_another_tenant(): void
    {
        $tenantA = $this->createTenantWithActiveDomain('thanks_a');
        $tenantB = $this->createTenantWithActiveDomain('thanks_b');

        $moto = Motorcycle::query()->create([
            'tenant_id' => $tenantA->id,
            'name' => 'Isolated Moto',
            'slug' => 'iso-moto',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 1000,
        ]);

        $booking = Booking::factory()
            ->forTenant($tenantA)
            ->withMotorcycle($moto)
            ->create([
                'booking_number' => 'BK-TENANT-A-ISOLATION',
            ]);

        $this->assertSame($tenantA->id, $booking->tenant_id);

        $response = $this->call(
            'GET',
            'http://'.$this->tenancyHostForSlug('thanks_b').'/thank-you/BK-TENANT-A-ISOLATION',
        );

        $response->assertOk();
        $response->assertViewHas('booking', null);
    }

    public function test_thank_you_ignores_session_booking_from_another_tenant(): void
    {
        $tenantA = $this->createTenantWithActiveDomain('sess_ty_a');
        $tenantB = $this->createTenantWithActiveDomain('sess_ty_b');

        $moto = Motorcycle::query()->create([
            'tenant_id' => $tenantA->id,
            'name' => 'Session Moto',
            'slug' => 'sess-moto',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 1000,
        ]);

        $bookingA = Booking::factory()
            ->forTenant($tenantA)
            ->withMotorcycle($moto)
            ->create([
                'booking_number' => 'BK-SESSION-TENANT-A',
            ]);

        $hostB = $this->tenancyHostForSlug('sess_ty_b');

        $withoutNumber = $this->withSession(['booking' => $bookingA])
            ->call('GET', 'http://'.$hostB.'/thank-you');
        $withoutNumber->assertOk();
        $withoutNumber->assertViewHas('booking', null);

        $withNumber = $this->withSession(['booking' => $bookingA])
            ->call('GET', 'http://'.$hostB.'/thank-you/BK-SESSION-TENANT-A');
        $withNumber->assertOk();
        $withNumber->assertViewHas('booking', null);
    }
}
