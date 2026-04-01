<?php

namespace Tests\Unit\Themes;

use Tests\TestCase;

class ThemeLegacyPathResolverTest extends TestCase
{
    public function test_maps_images_motolevins_avatars_to_theme_build(): void
    {
        if (is_file(public_path('themes/moto/marketing/hero-bg.png'))) {
            $this->markTestSkipped('public/themes shadows resources.');
        }

        $url = theme_platform_url_from_legacy_public_path('images/motolevins/avatars/avatar-1.png');
        $this->assertNotNull($url);
        $this->assertStringContainsString('theme/build/moto', (string) $url);
    }

    public function test_maps_typo_motolevin_prefix(): void
    {
        if (is_file(public_path('themes/moto/marketing/hero-bg.png'))) {
            $this->markTestSkipped('public/themes shadows resources.');
        }

        $url = theme_platform_url_from_legacy_public_path('images/motolevin/avatars/avatar-2.png');
        $this->assertNotNull($url);
        $this->assertStringContainsString('theme/build/moto', (string) $url);
    }
}
