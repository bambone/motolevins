<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TenantBrandingAssetUrlTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_storage_path_uses_public_disk_url(): void
    {
        Storage::disk('public')->put('tenants/1/logo/a.png', 'binary');

        $url = tenant_branding_asset_url('tenants/1/logo/a.png', '');

        $this->assertNotSame('', $url);
        $this->assertStringContainsString('tenants/1/logo/a.png', $url);
    }

    public function test_legacy_url_used_when_path_empty(): void
    {
        $this->assertSame(
            'https://cdn.example.com/logo.png',
            tenant_branding_asset_url('', 'https://cdn.example.com/logo.png')
        );
    }

    public function test_path_takes_precedence_over_legacy(): void
    {
        Storage::disk('public')->put('tenants/2/logo/b.png', 'x');

        $url = tenant_branding_asset_url('tenants/2/logo/b.png', 'https://legacy.example/ignored.png');

        $this->assertStringContainsString('tenants/2/logo/b.png', $url);
        $this->assertStringNotContainsString('legacy.example', $url);
    }

    public function test_empty_when_both_empty_or_null(): void
    {
        $this->assertSame('', tenant_branding_asset_url('', ''));
        $this->assertSame('', tenant_branding_asset_url(null, null));
    }
}
