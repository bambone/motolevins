<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\ContactChannels\ContactChannelType;
use App\ContactChannels\TenantContactChannelsStore;
use App\Http\Controllers\HomeController;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Tenant;
use App\Support\Typography\RussianTypography;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

/**
 * Дымовые проверки публичной витрины (типографика, final-cta без жёсткого wa.me) после правок в shared Blade.
 */
final class TenantPublicMotoLayoutSmokeTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
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

    public function test_home_hero_default_heading_contains_nbsp_between_short_preposition_and_next_word(): void
    {
        $tenant = $this->createTenantWithActiveDomain('moto_smoke_hero');
        HomeController::forgetCachedPayloadForTenant((int) $tenant->id);
        Cache::flush();

        $host = $this->tenancyHostForSlug('moto_smoke_hero');
        $html = $this->call('GET', 'http://'.$host.'/')->assertOk()->getContent();

        $expected = RussianTypography::tiePrepositionsToNextWord('Услуги в вашем городе');
        $this->assertStringContainsString($expected, $html);
    }

    public function test_final_cta_uses_tenant_whatsapp_not_hardcoded_motolevins(): void
    {
        $tenant = $this->createTenantWithActiveDomain('moto_smoke_fcta');
        $this->persistWhatsappOnly($tenant, '79995550112');

        $page = Page::query()->create([
            'tenant_id' => (int) $tenant->id,
            'name' => 'Главная',
            'slug' => 'home',
            'template' => 'default',
            'status' => 'published',
            'published_at' => now(),
        ]);
        PageSection::query()->create([
            'tenant_id' => (int) $tenant->id,
            'page_id' => (int) $page->id,
            'section_key' => 'final_cta',
            'section_type' => 'cta',
            'title' => 'CTA',
            'data_json' => [],
            'sort_order' => 100,
            'is_visible' => true,
            'status' => 'published',
        ]);

        HomeController::forgetCachedPayloadForTenant((int) $tenant->id);
        Cache::flush();

        $host = $this->tenancyHostForSlug('moto_smoke_fcta');
        $html = $this->call('GET', 'http://'.$host.'/')->assertOk()->getContent();

        $this->assertStringContainsString('wa.me/79995550112', $html);
        $this->assertStringNotContainsString('wa.me/79130608689', $html);
    }

    public function test_final_cta_does_not_render_whatsapp_link_when_tenant_has_no_whatsapp(): void
    {
        $tenant = $this->createTenantWithActiveDomain('moto_smoke_fcta_nowa');
        $raw = [];
        foreach (ContactChannelType::allForTenantConfig() as $type) {
            $raw[$type->value] = $this->channelRow(false, false, false, '');
        }
        app(TenantContactChannelsStore::class)->persist((int) $tenant->id, $raw);

        $page = Page::query()->create([
            'tenant_id' => (int) $tenant->id,
            'name' => 'Главная',
            'slug' => 'home',
            'template' => 'default',
            'status' => 'published',
            'published_at' => now(),
        ]);
        PageSection::query()->create([
            'tenant_id' => (int) $tenant->id,
            'page_id' => (int) $page->id,
            'section_key' => 'final_cta',
            'section_type' => 'cta',
            'title' => 'CTA',
            'data_json' => ['primary_action' => 'link', 'button_url' => (string) route('contacts')],
            'sort_order' => 100,
            'is_visible' => true,
            'status' => 'published',
        ]);

        HomeController::forgetCachedPayloadForTenant((int) $tenant->id);
        Cache::flush();

        $html = $this->call('GET', 'http://'.$this->tenancyHostForSlug('moto_smoke_fcta_nowa').'/')->assertOk()->getContent();
        $this->assertStringNotContainsString('wa.me/', $html);
    }

    public function test_final_cta_data_json_whatsapp_url_overrides_tenant_number(): void
    {
        $tenant = $this->createTenantWithActiveDomain('moto_smoke_fcta_ov');
        $this->persistWhatsappOnly($tenant, '79995550112');

        $page = Page::query()->create([
            'tenant_id' => (int) $tenant->id,
            'name' => 'Главная',
            'slug' => 'home',
            'template' => 'default',
            'status' => 'published',
            'published_at' => now(),
        ]);
        PageSection::query()->create([
            'tenant_id' => (int) $tenant->id,
            'page_id' => (int) $page->id,
            'section_key' => 'final_cta',
            'section_type' => 'cta',
            'title' => 'CTA',
            'data_json' => [
                'whatsapp_url' => 'https://wa.me/70000000099',
                'primary_action' => 'link',
            ],
            'sort_order' => 100,
            'is_visible' => true,
            'status' => 'published',
        ]);

        HomeController::forgetCachedPayloadForTenant((int) $tenant->id);
        Cache::flush();

        $html = $this->call('GET', 'http://'.$this->tenancyHostForSlug('moto_smoke_fcta_ov').'/')->assertOk()->getContent();
        $this->assertStringContainsString('wa.me/70000000099', $html);
        // Номер из final-cta переопределён секцией; дубли tenant-FAB в подвале/углу могут показывать канал из сервиса — не сравниваем с ним.
    }
}
