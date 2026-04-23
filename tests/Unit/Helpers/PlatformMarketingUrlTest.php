<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use Illuminate\Http\Request;
use Tests\TestCase;

class PlatformMarketingUrlTest extends TestCase
{
    public function test_platform_marketing_canonical_origin_from_organization_url(): void
    {
        config(['platform_marketing.organization.url' => 'https://rentbase.su']);

        $this->assertSame('https://rentbase.su', platform_marketing_canonical_origin());
    }

    public function test_platform_marketing_canonical_url_uses_canonical_host_and_path_without_query(): void
    {
        config(['platform_marketing.organization.url' => 'https://rentbase.su']);
        $r = Request::create('https://www.rentbase.su/features', 'GET', ['utm' => 'x']);
        $this->app->instance('request', $r);

        $this->assertSame('https://rentbase.su/features', platform_marketing_canonical_url());
    }
}
