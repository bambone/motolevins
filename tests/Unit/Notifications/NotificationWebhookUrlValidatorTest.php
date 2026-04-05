<?php

namespace Tests\Unit\Notifications;

use App\NotificationCenter\WebhookUrlValidator;
use Tests\TestCase;

class NotificationWebhookUrlValidatorTest extends TestCase
{
    private WebhookUrlValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = app(WebhookUrlValidator::class);
    }

    public function test_http_scheme_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('https');
        $this->validator->assertSafeHttpsUrl('http://example.com/hook');
    }

    public function test_https_public_host_allowed(): void
    {
        $this->validator->assertSafeHttpsUrl('https://example.com/path');
        $this->assertTrue(true);
    }

    public function test_localhost_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->validator->assertSafeHttpsUrl('https://localhost/x');
    }
}
