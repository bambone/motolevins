<?php

namespace Tests\Unit\Rules;

use App\Rules\CmsHrefRule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CmsHrefRuleTest extends TestCase
{
    #[Test]
    #[DataProvider('allowedProvider')]
    public function allows_safe_hrefs(string $value): void
    {
        $failed = false;
        (new CmsHrefRule)->validate('link', $value, function () use (&$failed): void {
            $failed = true;
        });
        $this->assertFalse($failed);
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function allowedProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'relative' => ['/contacts'];
        yield 'relative_with_hash' => ['/contacts#form'];
        yield 'relative_with_query' => ['/contacts?x=1'];
        yield 'hash' => ['#expert-inquiry'];
        yield 'https' => ['https://example.com/x'];
        yield 'mailto' => ['mailto:a@b.c'];
        yield 'tel' => ['tel:+79990001122'];
    }

    #[Test]
    #[DataProvider('rejectedProvider')]
    public function rejects_dangerous_hrefs(string $value): void
    {
        $failed = false;
        (new CmsHrefRule)->validate('link', $value, function () use (&$failed): void {
            $failed = true;
        });
        $this->assertTrue($failed);
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function rejectedProvider(): iterable
    {
        yield 'javascript' => ['javascript:alert(1)'];
        yield 'data' => ['data:text/html,base64'];
        yield 'unknown_scheme' => ['ftp://x'];
        yield 'protocol_relative' => ['//evil.com'];
        yield 'protocol_relative_path' => ['//evil.com/path'];
        yield 'protocol_relative_triple_slash' => ['///evil.com'];
        yield 'query_only' => ['?tab=seo'];
        yield 'control_char' => ["/contacts\x01x"];
    }
}
