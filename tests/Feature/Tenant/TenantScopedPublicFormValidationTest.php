<?php

namespace Tests\Feature\Tenant;

use App\Models\Bike;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

/**
 * Regression: public form requests must not accept tenant-scoped FKs from other tenants via unscoped {@code exists} rules.
 */
class TenantScopedPublicFormValidationTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_api_bookings_rejects_bike_id_from_another_tenant(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $tenantA = $this->createTenantWithActiveDomain('bike_scope_a');
        $tenantB = $this->createTenantWithActiveDomain('bike_scope_b');

        $otherBike = Bike::query()->create([
            'tenant_id' => $tenantB->id,
            'name' => 'Alien',
            'type' => 'sport',
            'engine' => 600,
            'price_per_day' => 400,
            'image' => null,
            'is_active' => true,
        ]);

        $response = $this->postJson('http://'.$this->tenancyHostForSlug('bike_scope_a').'/api/bookings', [
            'bike_id' => $otherBike->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'customer_name' => 'Test',
            'phone' => '+79991114455',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['bike_id']);
    }

    public function test_contact_inquiry_rejects_page_section_id_from_another_tenant(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $tenantA = $this->createTenantWithActiveDomain('cinq_scope_a');
        $tenantB = $this->createTenantWithActiveDomain('cinq_scope_b');

        $pageB = Page::query()->create([
            'tenant_id' => $tenantB->id,
            'name' => 'Contacts B',
            'slug' => 'contacts-b',
            'template' => 'default',
            'status' => 'published',
            'published_at' => now(),
            'show_in_main_menu' => false,
            'main_menu_sort_order' => 0,
        ]);

        $sectionB = PageSection::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'page_id' => $pageB->id,
            'section_key' => 'contact_ci',
            'section_type' => 'contact_inquiry',
            'title' => 'Form B',
            'data_json' => [
                'enabled' => true,
                'show_email' => false,
                'show_preferred_channel' => true,
            ],
            'sort_order' => 1,
            'is_visible' => true,
        ]);

        $response = $this->postJson('http://'.$this->tenancyHostForSlug('cinq_scope_a').'/api/tenant/contact-inquiry', [
            'page_section_id' => $sectionB->id,
            'name' => 'Cross',
            'phone' => '+79992223344',
            'message' => 'Hello world test message here.',
            'preferred_contact_channel' => 'phone',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['page_section_id']);
    }
}
