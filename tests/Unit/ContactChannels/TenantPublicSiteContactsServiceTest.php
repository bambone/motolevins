<?php

namespace Tests\Unit\ContactChannels;

use App\ContactChannels\ContactChannelType;
use App\ContactChannels\TenantContactChannelsStore;
use App\ContactChannels\TenantPublicSiteContactsService;
use App\Models\Tenant;
use App\Models\TenantSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TenantPublicSiteContactsServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): TenantPublicSiteContactsService
    {
        return app(TenantPublicSiteContactsService::class);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function baselineChannelRows(): array
    {
        $raw = [];
        foreach (ContactChannelType::allForTenantConfig() as $type) {
            $k = $type->value;
            $raw[$k] = [
                'uses_channel' => false,
                'public_visible' => false,
                'allowed_in_forms' => false,
                'business_value' => '',
                'sort_order' => 10,
            ];
        }

        return $raw;
    }

    public function test_messengers_empty_when_not_public_and_not_allowed_in_forms(): void
    {
        $tenant = Tenant::query()->create(['name' => 'X', 'slug' => 'tenant-x', 'status' => 'active']);
        TenantSetting::setForTenant($tenant->id, 'contacts.phone', '+79990001122');
        TenantSetting::setForTenant($tenant->id, 'contacts.whatsapp', '79990001122');
        TenantSetting::setForTenant($tenant->id, 'contacts.telegram', 'wronghandle');
        Cache::flush();

        $raw = $this->baselineChannelRows();
        $raw['whatsapp']['uses_channel'] = true;
        $raw['whatsapp']['public_visible'] = false;
        $raw['whatsapp']['allowed_in_forms'] = false;
        $raw['whatsapp']['business_value'] = '79990001122';
        $raw['telegram']['uses_channel'] = true;
        $raw['telegram']['public_visible'] = false;
        $raw['telegram']['allowed_in_forms'] = false;
        $raw['telegram']['business_value'] = 'wronghandle';

        app(TenantContactChannelsStore::class)->persist($tenant->id, $raw);
        Cache::flush();

        $c = $this->service()->contactsForPublicLayout($tenant);
        $this->assertSame('', $c['whatsapp']);
        $this->assertSame('', $c['telegram']);
        $this->assertSame('', $c['vk_url']);
    }

    public function test_whatsapp_digits_when_allowed_in_forms_without_public_visible(): void
    {
        $tenant = Tenant::query()->create(['name' => 'X2', 'slug' => 'tenant-x2', 'status' => 'active']);
        $raw = $this->baselineChannelRows();
        $raw['whatsapp']['uses_channel'] = true;
        $raw['whatsapp']['public_visible'] = false;
        $raw['whatsapp']['allowed_in_forms'] = true;
        $raw['whatsapp']['business_value'] = '+7 999 000-11-22';

        app(TenantContactChannelsStore::class)->persist($tenant->id, $raw);
        Cache::flush();

        $c = $this->service()->contactsForPublicLayout($tenant);
        $this->assertSame('79990001122', $c['whatsapp']);
    }

    public function test_vk_url_when_allowed_in_forms_without_public_visible(): void
    {
        $tenant = Tenant::query()->create(['name' => 'X3', 'slug' => 'tenant-x3', 'status' => 'active']);
        $raw = $this->baselineChannelRows();
        $raw['vk']['uses_channel'] = true;
        $raw['vk']['public_visible'] = false;
        $raw['vk']['allowed_in_forms'] = true;
        $raw['vk']['business_value'] = 'https://vk.com/club123';

        app(TenantContactChannelsStore::class)->persist($tenant->id, $raw);
        Cache::flush();

        $c = $this->service()->contactsForPublicLayout($tenant);
        $this->assertSame('https://vk.com/club123', $c['vk_url']);
    }

    public function test_whatsapp_digits_when_public_visible(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Y', 'slug' => 'tenant-y', 'status' => 'active']);
        $raw = $this->baselineChannelRows();
        $raw['whatsapp']['uses_channel'] = true;
        $raw['whatsapp']['public_visible'] = true;
        $raw['whatsapp']['business_value'] = '+7 999 111-22-33';

        app(TenantContactChannelsStore::class)->persist($tenant->id, $raw);
        Cache::flush();

        $c = $this->service()->contactsForPublicLayout($tenant);
        $this->assertSame('79991112233', $c['whatsapp']);
    }

    public function test_floating_enabled_defaults_true(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Z', 'slug' => 'tenant-z', 'status' => 'active']);
        Cache::flush();
        $this->assertTrue($this->service()->floatingMessengerButtonsEnabled((int) $tenant->id));
    }

    public function test_floating_can_be_disabled(): void
    {
        $tenant = Tenant::query()->create(['name' => 'W', 'slug' => 'tenant-w', 'status' => 'active']);
        TenantSetting::setForTenant($tenant->id, 'public_site.floating_messenger_buttons', false, 'boolean');
        Cache::flush();
        $this->assertFalse($this->service()->floatingMessengerButtonsEnabled((int) $tenant->id));
    }

    public function test_footer_messenger_links_follows_floating_when_unset(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Fm1', 'slug' => 'tenant-fm1', 'status' => 'active']);
        TenantSetting::setForTenant($tenant->id, 'public_site.floating_messenger_buttons', false, 'boolean');
        Cache::flush();
        $this->assertFalse($this->service()->footerMessengerLinksEnabled((int) $tenant->id));
    }

    public function test_footer_messenger_links_can_show_when_floating_disabled(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Fm2', 'slug' => 'tenant-fm2', 'status' => 'active']);
        TenantSetting::setForTenant($tenant->id, 'public_site.floating_messenger_buttons', false, 'boolean');
        TenantSetting::setForTenant($tenant->id, 'public_site.footer_messenger_links', true, 'boolean');
        Cache::flush();
        $this->assertTrue($this->service()->footerMessengerLinksEnabled((int) $tenant->id));
    }

    public function test_footer_messenger_string_false_normalizes_like_service_not_raw_bool_cast(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Fm3', 'slug' => 'tenant-fm3', 'status' => 'active']);
        TenantSetting::setForTenant($tenant->id, 'public_site.footer_messenger_links', 'false', 'string');
        Cache::flush();
        $this->assertFalse($this->service()->footerMessengerLinksEnabled((int) $tenant->id));
        $this->assertSame('hide', $this->service()->footerMessengerLinksModeForForm((int) $tenant->id));
    }

    public function test_each_tenant_gets_own_whatsapp_digits_from_persisted_channels(): void
    {
        $t1 = Tenant::query()->create(['name' => 'Iso1', 'slug' => 'iso-svc-1', 'status' => 'active']);
        $t2 = Tenant::query()->create(['name' => 'Iso2', 'slug' => 'iso-svc-2', 'status' => 'active']);

        $rawBase = $this->baselineChannelRows();
        $raw1 = $rawBase;
        $raw1['whatsapp'] = ['uses_channel' => true, 'public_visible' => true, 'allowed_in_forms' => true, 'business_value' => '79991110001', 'sort_order' => 10];
        $raw2 = $rawBase;
        $raw2['whatsapp'] = ['uses_channel' => true, 'public_visible' => true, 'allowed_in_forms' => true, 'business_value' => '79992220002', 'sort_order' => 10];

        app(TenantContactChannelsStore::class)->persist((int) $t1->id, $raw1);
        app(TenantContactChannelsStore::class)->persist((int) $t2->id, $raw2);
        Cache::flush();

        $c1 = $this->service()->contactsForPublicLayout($t1);
        $c2 = $this->service()->contactsForPublicLayout($t2);

        $this->assertSame('79991110001', $c1['whatsapp']);
        $this->assertSame('79992220002', $c2['whatsapp']);
        $this->assertNotSame($c1['whatsapp'], $c2['whatsapp']);
    }
}
