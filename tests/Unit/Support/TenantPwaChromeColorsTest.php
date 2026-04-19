<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Models\Tenant;
use App\Models\TenantPushSettings;
use App\Support\TenantPwaChromeColors;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TenantPwaChromeColorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_theme_color_defaults_when_no_tenant(): void
    {
        $this->assertSame('#0c0c0e', TenantPwaChromeColors::themeColor(null));
        $this->assertSame('#0c0c0e', TenantPwaChromeColors::backgroundColor(null));
    }

    public function test_advocate_editorial_theme_without_push_override(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'A',
            'slug' => 'adv-color',
            'status' => 'active',
            'theme_key' => 'advocate_editorial',
        ]);

        $this->assertSame('#f4f1eb', TenantPwaChromeColors::themeColor($tenant));
        $this->assertSame('#0c0c0e', TenantPwaChromeColors::backgroundColor($tenant));
    }

    public function test_push_settings_override_colors(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'B',
            'slug' => 'push-colors',
            'status' => 'active',
        ]);

        TenantPushSettings::query()->create([
            'tenant_id' => $tenant->id,
            'pwa_theme_color' => '#112233',
            'pwa_background_color' => '#aabbcc',
        ]);

        $tenant->refresh();

        $this->assertSame('#112233', TenantPwaChromeColors::themeColor($tenant));
        $this->assertSame('#aabbcc', TenantPwaChromeColors::backgroundColor($tenant));
    }
}
