<?php

namespace Tests\Support;

use Illuminate\Testing\TestResponse;

trait InteractsWithTenantSmokeHttp
{
    protected function getTenantHtmlResponse(string $host, string $path): TestResponse
    {
        $path = str_starts_with($path, '/') ? $path : '/'.$path;

        return $this->call('GET', 'http://'.$host.$path);
    }
}
