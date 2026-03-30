<?php

namespace Tests\Feature\CRM;

use App\Http\Middleware\ResolveTenantFromDomain;
use App\Http\Responses\FilamentAccessDeniedRedirect;
use App\Models\Lead;
use App\Models\User;
use App\Tenant\CurrentTenant;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

class CrmRequestFilamentIsolationTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    protected function getWithHost(string $host, string $path): TestResponse
    {
        $path = str_starts_with($path, '/') ? $path : '/'.$path;

        return $this->call('GET', 'http://'.$host.$path);
    }

    protected function platformHost(): string
    {
        return trim((string) config('app.platform_host'));
    }

    public function test_tenant_list_shows_own_crm_rows_and_hides_other_tenant_markers(): void
    {
        $tenantA = $this->createTenantWithActiveDomain('ta');
        $tenantB = $this->createTenantWithActiveDomain('tb');

        $secretA = 'unique-marker-a-'.uniqid('', true).'@example.test';
        $secretB = 'unique-marker-b-'.uniqid('', true).'@example.test';

        $this->makeCrmRequest($tenantA->id, ['email' => $secretA, 'name' => 'Name A']);
        $this->makeCrmRequest($tenantB->id, ['email' => $secretB, 'name' => 'Name B']);

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenantA->id, ['role' => 'operator', 'status' => 'active']);

        $response = $this->actingAs($user)->getWithHost($this->tenancyHostForSlug('ta'), '/admin/crm-requests');

        $response->assertOk();
        $response->assertSee($secretA, false);
        $response->assertDontSee($secretB, false);
    }

    public function test_tenant_cannot_view_other_tenant_crm_record_via_direct_url(): void
    {
        $tenantA = $this->createTenantWithActiveDomain('ta');
        $tenantB = $this->createTenantWithActiveDomain('tb');

        $secretB = 'idor-b-'.uniqid('', true).'@example.test';
        $crmB = $this->makeCrmRequest($tenantB->id, ['email' => $secretB]);

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenantA->id, ['role' => 'operator', 'status' => 'active']);

        $response = $this->actingAs($user)->getWithHost(
            $this->tenancyHostForSlug('ta'),
            '/admin/crm-requests/'.$crmB->id
        );

        // ViewCrmRequest::resolveRecord uses tenant-scoped getEloquentQuery() → ModelNotFound → 404 (no id existence leak).
        $response->assertNotFound();
        $response->assertDontSee($secretB, false);
    }

    public function test_tenant_can_view_own_crm_record_via_direct_url(): void
    {
        $tenantA = $this->createTenantWithActiveDomain('tview');
        $secretA = 'own-view-'.uniqid('', true).'@example.test';
        $crmA = $this->makeCrmRequest($tenantA->id, ['email' => $secretA]);

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenantA->id, ['role' => 'operator', 'status' => 'active']);

        $response = $this->actingAs($user)->getWithHost(
            $this->tenancyHostForSlug('tview'),
            '/admin/crm-requests/'.$crmA->id
        );

        $response->assertOk();
        $response->assertSee($secretA, false);
    }

    public function test_corrupted_lead_crm_link_same_as_foreign_crm_id_does_not_leak_tenant_b_pii(): void
    {
        $tenantA = $this->createTenantWithActiveDomain('ta');
        $tenantB = $this->createTenantWithActiveDomain('tb');

        $secretB = 'corrupt-b-'.uniqid('', true).'@example.test';
        $crmB = $this->makeCrmRequest($tenantB->id, ['email' => $secretB]);

        Lead::query()->create([
            'tenant_id' => $tenantA->id,
            'crm_request_id' => $crmB->id,
            'name' => 'Lead A',
            'phone' => '+79990001122',
            'email' => 'lead-a@example.test',
            'source' => 'booking_form',
            'status' => 'new',
        ]);

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenantA->id, ['role' => 'operator', 'status' => 'active']);

        $response = $this->actingAs($user)->getWithHost(
            $this->tenancyHostForSlug('ta'),
            '/admin/crm-requests/'.$crmB->id
        );

        $this->assertNotSame(500, $response->getStatusCode());
        $response->assertDontSee($secretB, false);
        $this->assertTrue(
            $response->isRedirect() || $response->isNotFound(),
            'Expected redirect or 404 for corrupted CRM link, got '.$response->getStatusCode()
        );

        if ($response->isRedirect()) {
            $response->assertSessionHas(FilamentAccessDeniedRedirect::SESSION_KEY);
            $location = (string) $response->headers->get('Location');
            $this->assertStringNotContainsString($secretB, $location);
            $this->actingAs($user)->get($location)->assertDontSee($secretB, false);
        }
    }

    public function test_platform_list_shows_platform_crm_and_hides_tenant_markers(): void
    {
        $tenantA = $this->createTenantWithActiveDomain('ta');

        $secretPlat = 'plat-list-'.uniqid('', true).'@example.test';
        $secretTenant = 'tenant-list-'.uniqid('', true).'@example.test';

        $this->makeCrmRequest(null, ['email' => $secretPlat]);
        $this->makeCrmRequest($tenantA->id, ['email' => $secretTenant]);

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('platform_owner');

        $host = $this->platformHost();
        $response = $this->actingAs($user)->getWithHost($host, '/crm-requests');

        $response->assertOk();
        $response->assertSee($secretPlat, false);
        $response->assertDontSee($secretTenant, false);
    }

    public function test_platform_cannot_view_tenant_crm_record_without_leaking_pii(): void
    {
        $tenantA = $this->createTenantWithActiveDomain('ta');
        $secretT = 'plat-idor-'.uniqid('', true).'@example.test';
        $tenantCrm = $this->makeCrmRequest($tenantA->id, ['email' => $secretT]);

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('platform_owner');

        $host = $this->platformHost();
        $response = $this->actingAs($user)->getWithHost($host, '/crm-requests/'.$tenantCrm->id);

        $response->assertDontSee($secretT, false);
        $this->assertTrue(
            $response->isRedirect() || $response->isNotFound(),
            'Expected redirect or 404 for platform view of tenant CRM, got '.$response->getStatusCode()
        );

        if ($response->isRedirect()) {
            $response->assertSessionHas(FilamentAccessDeniedRedirect::SESSION_KEY);
            $location = (string) $response->headers->get('Location');
            $this->assertStringNotContainsString($secretT, $location);
            $this->actingAs($user)->get($location)->assertDontSee($secretT, false);
        }
    }

    public function test_tenant_admin_without_resolved_tenant_context_does_not_show_crm_rows(): void
    {
        $tenantA = $this->createTenantWithActiveDomain('ta');
        $secret = 'no-tenant-ctx-'.uniqid('', true).'@example.test';
        $this->makeCrmRequest($tenantA->id, ['email' => $secret]);

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenantA->id, ['role' => 'operator', 'status' => 'active']);

        $this->withoutMiddleware([ResolveTenantFromDomain::class]);
        $this->app->instance(CurrentTenant::class, new CurrentTenant(null, null, false, $this->tenancyHostForSlug('ta')));

        $response = $this->actingAs($user)->getWithHost($this->tenancyHostForSlug('ta'), '/admin/crm-requests');

        $this->assertNotSame(500, $response->getStatusCode());
        $response->assertDontSee($secret, false);
    }

    public function test_user_without_membership_on_host_tenant_cannot_access_admin_crm_list(): void
    {
        $this->createTenantWithActiveDomain('ta');
        $tenantB = $this->createTenantWithActiveDomain('tb');

        $secretB = 'host-mismatch-'.uniqid('', true).'@example.test';
        $this->makeCrmRequest($tenantB->id, ['email' => $secretB]);

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenantB->id, ['role' => 'operator', 'status' => 'active']);

        $response = $this->actingAs($user)->getWithHost($this->tenancyHostForSlug('ta'), '/admin/crm-requests');

        $response->assertRedirect();
        $response->assertSessionHas(FilamentAccessDeniedRedirect::SESSION_KEY);
        $response->assertDontSee($secretB, false);

        $location = (string) $response->headers->get('Location');
        $this->assertStringContainsString('login', $location);
    }
}
