<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\ContactChannels\ContactChannelType;
use App\ContactChannels\TenantContactChannelsStore;
use App\ContactChannels\TenantPublicSiteContactsService;
use App\Models\Tenant;
use App\Support\FinalCtaWhatsappUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

final class FinalCtaWhatsappUrlTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    /**
     * @return array<string, array<string, mixed>>
     */
    private function channelRow(bool $uses, bool $public, bool $forms, string $business): array
    {
        return [
            'uses_channel' => $uses,
            'public_visible' => $public,
            'allowed_in_forms' => $forms,
            'business_value' => $business,
            'sort_order' => 10,
        ];
    }

    private function persistWhatsapp(Tenant $tenant, string $digits): void
    {
        $raw = [];
        foreach (ContactChannelType::allForTenantConfig() as $type) {
            $k = $type->value;
            $raw[$k] = $this->channelRow(false, false, false, '');
        }
        $raw['whatsapp'] = $this->channelRow(true, true, true, $digits);
        app(TenantContactChannelsStore::class)->persist((int) $tenant->id, $raw);
    }

    public function test_prop_url_wins_over_section_and_service(): void
    {
        $tenant = $this->createTenantWithActiveDomain('fcu_prop');
        $this->persistWhatsapp($tenant, '79991111111');
        $svc = app(TenantPublicSiteContactsService::class);
        $out = FinalCtaWhatsappUrl::resolve(
            'https://wa.me/70000000001',
            'https://wa.me/70000000002',
            true,
            $tenant,
            $svc,
        );
        $this->assertSame('https://wa.me/70000000001', $out);
    }

    public function test_section_url_wins_over_service_when_prop_not_set(): void
    {
        $tenant = $this->createTenantWithActiveDomain('fcu_sec');
        $this->persistWhatsapp($tenant, '79991111111');
        $svc = app(TenantPublicSiteContactsService::class);
        $out = FinalCtaWhatsappUrl::resolve(
            null,
            'https://wa.me/70000000002',
            true,
            $tenant,
            $svc,
        );
        $this->assertSame('https://wa.me/70000000002', $out);
    }

    public function test_section_digits_normalized_to_wa_me(): void
    {
        $tenant = $this->createTenantWithActiveDomain('fcu_digits');
        $this->persistWhatsapp($tenant, '79991111111');
        $out = FinalCtaWhatsappUrl::resolve(
            null,
            '8 (900) 000-11-22',
            true,
            $tenant,
            app(TenantPublicSiteContactsService::class),
        );
        $this->assertSame('https://wa.me/89000001122', $out);
    }

    public function test_uses_tenant_service_when_no_prop_or_section(): void
    {
        $tenant = $this->createTenantWithActiveDomain('fcu_svc');
        $this->persistWhatsapp($tenant, '79995550112');
        $out = FinalCtaWhatsappUrl::resolve(
            null,
            '',
            true,
            $tenant,
            app(TenantPublicSiteContactsService::class),
        );
        $this->assertSame('https://wa.me/79995550112', $out);
    }

    public function test_returns_empty_when_show_secondary_off(): void
    {
        $tenant = $this->createTenantWithActiveDomain('fcu_off');
        $this->persistWhatsapp($tenant, '79995550112');
        $out = FinalCtaWhatsappUrl::resolve(
            'https://wa.me/70000000001',
            '',
            false,
            $tenant,
            app(TenantPublicSiteContactsService::class),
        );
        $this->assertSame('', $out);
    }

    public function test_empty_string_prop_disables_whatsapp_without_fallback_to_section_or_service(): void
    {
        $tenant = $this->createTenantWithActiveDomain('fcu_empty_prop');
        $this->persistWhatsapp($tenant, '79991111111');
        $out = FinalCtaWhatsappUrl::resolve(
            '',
            'https://wa.me/70000000002',
            true,
            $tenant,
            app(TenantPublicSiteContactsService::class),
        );
        $this->assertSame('', $out);
    }
}
