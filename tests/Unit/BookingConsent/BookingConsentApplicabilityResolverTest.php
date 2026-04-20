<?php

declare(strict_types=1);

namespace Tests\Unit\BookingConsent;

use App\BookingConsent\BookingConsentApplicabilityResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

class BookingConsentApplicabilityResolverTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    public function test_store_checkout_route_is_booking_scenario(): void
    {
        $r = app(BookingConsentApplicabilityResolver::class);
        $req = Request::create('/checkout', 'POST');
        $req->setRouteResolver(static fn () => (new Route(['POST'], 'checkout', []))->name('booking.store-checkout'));

        $this->assertTrue($r->isBookingScenario($req));
    }

    public function test_api_leads_with_motorcycle_id_is_booking_scenario(): void
    {
        $r = app(BookingConsentApplicabilityResolver::class);
        $req = Request::create('/api/leads', 'POST', ['motorcycle_id' => 5]);
        $req->setRouteResolver(static fn () => (new Route(['POST'], 'api/leads', []))->name('api.leads.store'));

        $this->assertTrue($r->isBookingScenario($req));
    }

    public function test_lead_request_without_motorcycle_is_not_booking_scenario(): void
    {
        $this->createTenantWithActiveDomain('bcar');
        $r = app(BookingConsentApplicabilityResolver::class);
        $req = Request::create('/api/leads', 'POST', []);
        $req->setRouteResolver(static fn () => (new Route(['POST'], 'api/leads', []))->name('api.leads.store'));

        $this->assertFalse($r->isBookingScenarioForLeadRequest($req));
    }
}
