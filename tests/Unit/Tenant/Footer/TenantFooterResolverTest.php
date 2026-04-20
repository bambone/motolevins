<?php

declare(strict_types=1);

namespace Tests\Unit\Tenant\Footer;

use App\Models\Tenant;
use App\Models\TenantFooterSection;
use App\Tenant\Footer\FooterSectionType;
use App\Tenant\Footer\TenantFooterResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

class TenantFooterResolverTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    public function test_minimal_footer_when_no_sections(): void
    {
        $tenant = $this->createTenantWithActiveDomain('tfresolve');
        $tenantModel = Tenant::query()->whereKey($tenant->id)->firstOrFail();

        $data = app(TenantFooterResolver::class)->resolve($tenantModel);

        $this->assertSame('minimal', $data['mode']);
        $this->assertSame([], $data['sections']);
        $this->assertNotSame('', $data['site_name']);
        $this->assertGreaterThan(2000, $data['year']);
        $this->assertIsArray($data['contact_presentation'] ?? null);
        $this->assertArrayHasKey('telegram_url', $data['contact_presentation']);
        $this->assertNotSame('', $data['minimal_service_note'] ?? '');
        $this->assertNotSame('', $data['minimal_booking_subline'] ?? '');
    }

    public function test_skips_invalid_meta_and_keeps_valid_sections(): void
    {
        $tenant = $this->createTenantWithActiveDomain('tfmixed');
        $tid = (int) $tenant->id;

        TenantFooterSection::query()->create([
            'tenant_id' => $tid,
            'section_key' => 'bad',
            'type' => FooterSectionType::GEO_POINTS,
            'meta_json' => ['headline' => 'X', 'items' => []],
            'sort_order' => 0,
            'is_enabled' => true,
        ]);

        TenantFooterSection::query()->create([
            'tenant_id' => $tid,
            'section_key' => 'ok',
            'type' => FooterSectionType::GEO_POINTS,
            'meta_json' => [
                'headline' => 'Зона',
                'items' => ['Пункт 1'],
            ],
            'sort_order' => 1,
            'is_enabled' => true,
        ]);

        $data = app(TenantFooterResolver::class)->resolve(Tenant::query()->findOrFail($tid));

        $this->assertSame('full', $data['mode']);
        $this->assertCount(1, $data['sections']);
        $this->assertSame(FooterSectionType::GEO_POINTS, $data['sections'][0]['type']);
        $this->assertArrayHasKey('footer_tagline', $data);
    }
}
