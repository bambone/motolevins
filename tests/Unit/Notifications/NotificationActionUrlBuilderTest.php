<?php

namespace Tests\Unit\Notifications;

use App\Models\Booking;
use App\Models\CrmRequest;
use App\Models\Lead;
use App\Models\Tenant;
use App\Models\TenantDomain;
use App\NotificationCenter\NotificationActionUrlBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationActionUrlBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_builds_tenant_admin_https_url_when_subject_exists_in_tenant(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'T',
            'slug' => 'u-'.substr(uniqid(), -10),
            'status' => 'active',
        ]);
        TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'host' => 'acme.apex.test',
            'type' => TenantDomain::TYPE_SUBDOMAIN,
            'is_primary' => true,
            'status' => TenantDomain::STATUS_ACTIVE,
            'ssl_status' => TenantDomain::SSL_NOT_REQUIRED,
            'verified_at' => now(),
            'activated_at' => now(),
        ]);

        $crm = CrmRequest::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'X',
            'phone' => '+70000000000',
            'email' => null,
            'message' => 'm',
            'request_type' => 'tenant_booking',
            'source' => 'test',
            'channel' => 'web',
            'pipeline' => 'inbound',
            'status' => CrmRequest::STATUS_NEW,
            'last_activity_at' => now(),
        ]);

        $builder = app(NotificationActionUrlBuilder::class);
        $url = $builder->urlForSubject($tenant, class_basename(CrmRequest::class), (int) $crm->id);

        $this->assertNotNull($url);
        $this->assertStringStartsWith('https://acme.apex.test/admin', $url);
        $this->assertStringEndsWith('/crm-requests/'.$crm->id, $url);
        $this->assertStringNotContainsString('platform.apex.test', $url);
        $this->assertStringNotContainsString('apex.test/platform', $url);
    }

    public function test_returns_null_when_subject_missing_or_wrong_tenant(): void
    {
        $tenantA = Tenant::query()->create(['name' => 'A', 'slug' => 'a-'.substr(uniqid(), -10), 'status' => 'active']);
        $tenantB = Tenant::query()->create(['name' => 'B', 'slug' => 'b-'.substr(uniqid(), -10), 'status' => 'active']);
        TenantDomain::query()->create([
            'tenant_id' => $tenantA->id,
            'host' => 'a.apex.test',
            'type' => TenantDomain::TYPE_SUBDOMAIN,
            'is_primary' => true,
            'status' => TenantDomain::STATUS_ACTIVE,
            'ssl_status' => TenantDomain::SSL_NOT_REQUIRED,
            'verified_at' => now(),
            'activated_at' => now(),
        ]);

        $crmOnB = CrmRequest::query()->create([
            'tenant_id' => $tenantB->id,
            'name' => 'X',
            'phone' => '+70000000001',
            'email' => null,
            'message' => 'm',
            'request_type' => 'tenant_booking',
            'source' => 'test',
            'channel' => 'web',
            'pipeline' => 'inbound',
            'status' => CrmRequest::STATUS_NEW,
            'last_activity_at' => now(),
        ]);

        $builder = app(NotificationActionUrlBuilder::class);
        $this->assertNull($builder->urlForSubject($tenantA, class_basename(CrmRequest::class), 999999));
        $this->assertNull($builder->urlForSubject($tenantA, class_basename(CrmRequest::class), (int) $crmOnB->id));
    }

    public function test_unsupported_subject_type_returns_null(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'T',
            'slug' => 'u-'.substr(uniqid(), -10),
            'status' => 'active',
        ]);
        TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'host' => 'x.apex.test',
            'type' => TenantDomain::TYPE_SUBDOMAIN,
            'is_primary' => true,
            'status' => TenantDomain::STATUS_ACTIVE,
            'ssl_status' => TenantDomain::SSL_NOT_REQUIRED,
            'verified_at' => now(),
            'activated_at' => now(),
        ]);

        $builder = app(NotificationActionUrlBuilder::class);
        $this->assertNull($builder->urlForSubject($tenant, 'UnknownThing', 1));
    }

    public function test_builds_paths_for_lead_and_booking_when_rows_exist(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'T',
            'slug' => 'u-'.substr(uniqid(), -10),
            'status' => 'active',
        ]);
        TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'host' => 'x.apex.test',
            'type' => TenantDomain::TYPE_SUBDOMAIN,
            'is_primary' => true,
            'status' => TenantDomain::STATUS_ACTIVE,
            'ssl_status' => TenantDomain::SSL_NOT_REQUIRED,
            'verified_at' => now(),
            'activated_at' => now(),
        ]);

        $lead = Lead::query()->create([
            'tenant_id' => $tenant->id,
            'crm_request_id' => null,
            'name' => 'L',
            'phone' => '+70000000002',
            'email' => null,
            'source' => 'test',
            'status' => 'new',
        ]);

        $booking = Booking::factory()->forTenant($tenant)->create();

        $builder = app(NotificationActionUrlBuilder::class);
        $this->assertStringEndsWith('/leads/'.$lead->id, (string) $builder->urlForSubject($tenant, class_basename(Lead::class), (int) $lead->id));
        $this->assertStringEndsWith('/bookings/'.$booking->id, (string) $builder->urlForSubject($tenant, class_basename(Booking::class), (int) $booking->id));
    }

    public function test_returns_null_without_active_domain(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'T',
            'slug' => 'u-'.substr(uniqid(), -10),
            'status' => 'active',
        ]);
        $builder = app(NotificationActionUrlBuilder::class);
        $this->assertNull($builder->urlForSubject($tenant, class_basename(CrmRequest::class), 1));
    }
}
