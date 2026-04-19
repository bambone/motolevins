<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\ContactChannels\ContactChannelType;
use App\ContactChannels\TenantContactChannelsStore;
use App\ContactChannels\TenantPublicSiteContactsService;
use App\Models\Tenant;
use App\Models\TenantSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

/**
 * Публичные контакты в Blade ($contacts) привязаны к текущему тенанту по хосту: нельзя подмешать WA/TG/VK другого клиента.
 * Шаблоны общие (tenant.layouts.app); изоляция — в View::composer + {@see TenantPublicSiteContactsService}.
 */
final class TenantPublicContactsComposerIsolationTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function getWithHost(string $host, string $path): TestResponse
    {
        $path = str_starts_with($path, '/') ? $path : '/'.$path;

        return $this->call('GET', 'http://'.$host.$path);
    }

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

    private function persistWhatsappOnly(Tenant $tenant, string $digits): void
    {
        $raw = [];
        foreach (ContactChannelType::allForTenantConfig() as $type) {
            $k = $type->value;
            $raw[$k] = $this->channelRow(false, false, false, '');
        }
        $raw['whatsapp'] = $this->channelRow(true, true, true, $digits);

        app(TenantContactChannelsStore::class)->persist((int) $tenant->id, $raw);
    }

    public function test_public_prices_page_shows_only_current_tenant_whatsapp_link(): void
    {
        $tenantA = $this->createTenantWithActiveDomain('iso_pub_a', ['theme_key' => 'default']);
        $tenantB = $this->createTenantWithActiveDomain('iso_pub_b', ['theme_key' => 'default']);

        $this->persistWhatsappOnly($tenantA, '79991111111');
        $this->persistWhatsappOnly($tenantB, '79992222222');
        Cache::flush();

        $hostA = $this->tenancyHostForSlug('iso_pub_a');
        $hostB = $this->tenancyHostForSlug('iso_pub_b');

        $htmlA = $this->getWithHost($hostA, '/prices')->assertOk()->getContent();
        $htmlB = $this->getWithHost($hostB, '/prices')->assertOk()->getContent();

        $this->assertStringContainsString('wa.me/79991111111', $htmlA);
        $this->assertStringNotContainsString('wa.me/79992222222', $htmlA);

        $this->assertStringContainsString('wa.me/79992222222', $htmlB);
        $this->assertStringNotContainsString('wa.me/79991111111', $htmlB);
    }

    public function test_floating_messenger_toggle_is_per_tenant(): void
    {
        $tenantA = $this->createTenantWithActiveDomain('iso_fab_a', ['theme_key' => 'default']);
        $tenantB = $this->createTenantWithActiveDomain('iso_fab_b', ['theme_key' => 'default']);

        $this->persistWhatsappOnly($tenantA, '79993333333');
        $this->persistWhatsappOnly($tenantB, '79994444444');

        TenantSetting::setForTenant((int) $tenantA->id, 'public_site.floating_messenger_buttons', false, 'boolean');
        Cache::flush();

        $hostA = $this->tenancyHostForSlug('iso_fab_a');
        $hostB = $this->tenancyHostForSlug('iso_fab_b');

        $htmlA = $this->getWithHost($hostA, '/prices')->assertOk()->getContent();
        $htmlB = $this->getWithHost($hostB, '/prices')->assertOk()->getContent();

        $this->assertStringNotContainsString('wa.me/79993333333', $htmlA);
        $this->assertStringContainsString('wa.me/79994444444', $htmlB);
    }
}
