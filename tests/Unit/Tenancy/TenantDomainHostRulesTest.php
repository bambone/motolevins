<?php

declare(strict_types=1);

namespace Tests\Unit\Tenancy;

use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Services\Tenancy\TenantDomainHostRules;
use App\Tenant\HostClassifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class TenantDomainHostRulesTest extends TestCase
{
    use RefreshDatabase;

    private function rules(): TenantDomainHostRules
    {
        return new TenantDomainHostRules(new HostClassifier);
    }

    public function test_accepts_valid_hosts(): void
    {
        $r = $this->rules();

        foreach (['example.com', 'sub.example.com', 'moto-levins.ru', 'xn--d1acpjx3f.xn--p1ai'] as $host) {
            $this->assertSame(
                $host,
                $r->assertValidHostFormat($host, TenantDomain::TYPE_CUSTOM),
                'failed for '.$host
            );
        }
    }

    public function test_normalizes_case_and_trailing_dot(): void
    {
        $r = $this->rules();

        $this->assertSame('example.com', $r->assertValidHostFormat('Example.COM', TenantDomain::TYPE_CUSTOM));
        $this->assertSame('example.com', $r->assertValidHostFormat('example.com.', TenantDomain::TYPE_CUSTOM));
    }

    public function test_rejects_invalid_hosts(): void
    {
        $r = $this->rules();

        foreach ([
            'invalid domain',
            'http://example.com',
            'https://example.com',
            'example',
            '.example.com',
            'example..com',
            '-example.com',
            'example-.com',
            'exa_mple.com',
            'example.com/path',
            'localhost',
            '127.0.0.1',
            '*.example.com',
            ' example.com',
        ] as $bad) {
            try {
                $r->assertValidHostFormat($bad, TenantDomain::TYPE_CUSTOM);
                $this->fail('Expected validation failure for: '.$bad);
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_custom_domain_cannot_use_platform_subdomain_zone(): void
    {
        $r = $this->rules();
        $tenant = Tenant::query()->create([
            'name' => 'T1',
            'slug' => 't1',
            'status' => 'active',
        ]);

        $canonical = $r->assertValidHostFormat('mine.apex.test', TenantDomain::TYPE_CUSTOM);

        $this->expectException(ValidationException::class);
        $r->assertAttachableOrThrow($canonical, $tenant->id, null, TenantDomain::TYPE_CUSTOM);
    }

    public function test_subdomain_type_allows_tenant_under_root_zone(): void
    {
        $r = $this->rules();
        $tenant = Tenant::query()->create([
            'name' => 'T2',
            'slug' => 't2',
            'status' => 'active',
        ]);

        $canonical = $r->assertValidHostFormat('t2.apex.test', TenantDomain::TYPE_SUBDOMAIN);
        $r->assertAttachableOrThrow($canonical, $tenant->id, null, TenantDomain::TYPE_SUBDOMAIN);

        $this->addToAssertionCount(1);
    }

    public function test_rejects_duplicate_host_for_other_tenant(): void
    {
        $r = $this->rules();
        $t1 = Tenant::query()->create(['name' => 'A', 'slug' => 'a', 'status' => 'active']);
        $t2 = Tenant::query()->create(['name' => 'B', 'slug' => 'b', 'status' => 'active']);

        TenantDomain::query()->create([
            'tenant_id' => $t1->id,
            'host' => 'shared.example.test',
            'type' => TenantDomain::TYPE_CUSTOM,
            'is_primary' => true,
            'status' => TenantDomain::STATUS_PENDING,
            'ssl_status' => TenantDomain::SSL_PENDING,
        ]);

        $this->expectException(ValidationException::class);
        $r->validateAndCanonicalize('shared.example.test', $t2->id, null, TenantDomain::TYPE_CUSTOM);
    }

    public function test_idn_unicode_becomes_punycode_when_intl_available(): void
    {
        if (! function_exists('idn_to_ascii')) {
            $this->markTestSkipped('intl extension not available');
        }

        $r = $this->rules();
        $out = $r->assertValidHostFormat('müller.example.com', TenantDomain::TYPE_CUSTOM);
        $this->assertStringContainsString('xn--', $out);
    }

    public function test_subdomain_type_allows_underscore_from_slug(): void
    {
        $r = $this->rules();
        $out = $r->assertValidHostFormat('my_tenant.apex.test', TenantDomain::TYPE_SUBDOMAIN);
        $this->assertSame('my_tenant.apex.test', $out);
    }
}
