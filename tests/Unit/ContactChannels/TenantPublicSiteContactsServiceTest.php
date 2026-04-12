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

    public function test_messengers_empty_when_public_visible_off_even_if_values_present(): void
    {
        $tenant = Tenant::query()->create(['name' => 'X', 'slug' => 'tenant-x', 'status' => 'active']);
        TenantSetting::setForTenant($tenant->id, 'contacts.phone', '+79990001122');
        TenantSetting::setForTenant($tenant->id, 'contacts.whatsapp', '79990001122');
        TenantSetting::setForTenant($tenant->id, 'contacts.telegram', 'wronghandle');
        Cache::flush();

        $raw = $this->baselineChannelRows();
        $raw['whatsapp']['uses_channel'] = true;
        $raw['whatsapp']['public_visible'] = false;
        $raw['whatsapp']['business_value'] = '79990001122';
        $raw['telegram']['uses_channel'] = true;
        $raw['telegram']['public_visible'] = false;
        $raw['telegram']['business_value'] = 'wronghandle';

        app(TenantContactChannelsStore::class)->persist($tenant->id, $raw);
        Cache::flush();

        $c = $this->service()->contactsForPublicLayout($tenant);
        $this->assertSame('', $c['whatsapp']);
        $this->assertSame('', $c['telegram']);
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
}
