<?php

namespace Tests\Feature\SEO;

use App\Models\PlatformSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Central marketing host (see phpunit.xml TENANCY_CENTRAL_DOMAINS).
 */
class PlatformMarketingSeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Cache::flush();
    }

    protected function getWithHost(string $host, string $path = '/'): TestResponse
    {
        $path = str_starts_with($path, '/') ? $path : '/'.$path;

        return $this->call('GET', 'http://'.$host.$path);
    }

    public function test_custom_robots_txt_is_served_when_enabled_and_non_empty(): void
    {
        PlatformSetting::set('marketing.seo.custom_robots_enabled', true, 'boolean');
        PlatformSetting::set('marketing.seo.robots_txt', "User-agent: *\nDisallow: /tmp-custom-test/", 'string');

        $this->getWithHost('apex.test', '/robots.txt')
            ->assertOk()
            ->assertSee('tmp-custom-test', false);
    }

    public function test_custom_sitemap_paths_replace_defaults(): void
    {
        PlatformSetting::set('marketing.seo.sitemap_paths', ['/custom-only', '/x'], 'json');

        $response = $this->getWithHost('apex.test', '/sitemap.xml');
        $response->assertOk();
        $response->assertSee('http://apex.test/custom-only', false);
        $response->assertSee('http://apex.test/x', false);
        $response->assertDontSee('http://apex.test/pricing', false);
    }

    public function test_llms_txt_reflects_platform_setting_intro_and_entries(): void
    {
        PlatformSetting::set('marketing.seo.llms_intro', 'INTRO_LINE_ONE', 'string');
        PlatformSetting::set(
            'marketing.seo.llms_entries',
            json_encode([
                ['path' => '/about', 'summary' => 'About page'],
            ], JSON_UNESCAPED_UNICODE),
            'string',
        );

        $this->getWithHost('apex.test', '/llms.txt')
            ->assertOk()
            ->assertSee('INTRO_LINE_ONE', false)
            ->assertSee('http://apex.test/about', false)
            ->assertSee('About page', false);
    }
}
