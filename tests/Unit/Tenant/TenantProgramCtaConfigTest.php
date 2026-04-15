<?php

namespace Tests\Unit\Tenant;

use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Tenant\Expert\TenantProgramCtaConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantProgramCtaConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_mode_defaults_to_modal_when_setting_missing(): void
    {
        $t = Tenant::query()->create([
            'name' => 'CTA Test',
            'slug' => 'cta-test',
            'theme_key' => 'expert_auto',
            'currency' => 'RUB',
            'status' => 'active',
        ]);

        $cfg = new TenantProgramCtaConfig($t);
        $this->assertSame(TenantProgramCtaConfig::MODE_MODAL, $cfg->mode());
    }

    public function test_mode_reflects_tenant_setting(): void
    {
        $t = Tenant::query()->create([
            'name' => 'CTA Test 2',
            'slug' => 'cta-test-2',
            'theme_key' => 'expert_auto',
            'currency' => 'RUB',
            'status' => 'active',
        ]);

        TenantSetting::setForTenant($t->id, 'programs.cta_behavior', TenantProgramCtaConfig::MODE_SCROLL, 'string');
        $this->assertSame(TenantProgramCtaConfig::MODE_SCROLL, (new TenantProgramCtaConfig($t))->mode());

        TenantSetting::setForTenant($t->id, 'programs.cta_behavior', TenantProgramCtaConfig::MODE_PAGE, 'string');
        $this->assertSame(TenantProgramCtaConfig::MODE_PAGE, (new TenantProgramCtaConfig($t))->mode());

        TenantSetting::setForTenant($t->id, 'programs.cta_behavior', 'invalid', 'string');
        $this->assertSame(TenantProgramCtaConfig::MODE_MODAL, (new TenantProgramCtaConfig($t))->mode());
    }
}
