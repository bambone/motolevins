<?php

namespace Tests\Unit\ContactChannels;

use App\ContactChannels\VisitorContactNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class VisitorContactNormalizerTest extends TestCase
{
    #[DataProvider('telegramProvider')]
    public function test_normalize_telegram(string $raw, ?string $expected): void
    {
        $this->assertSame($expected, VisitorContactNormalizer::normalizeTelegram($raw));
    }

    /**
     * @return iterable<string, array{0: string, 1: ?string}>
     */
    public static function telegramProvider(): iterable
    {
        yield 'username' => ['Ivan_Ivanov', 'ivan_ivanov'];
        yield 'at' => ['@User_Name', 'user_name'];
        yield 'tme' => ['https://t.me/someuser', 'someuser'];
        yield 'bad' => ['ab', null];
    }

    public function test_normalize_vk_to_https_url(): void
    {
        $this->assertSame(
            'https://vk.com/durov',
            VisitorContactNormalizer::normalizeVk('durov')
        );
        $this->assertSame(
            'https://vk.com/id1',
            VisitorContactNormalizer::normalizeVk('https://vk.com/id1')
        );
    }

    public function test_normalize_max_accepts_url_or_string(): void
    {
        $this->assertSame('https://example.com/m', VisitorContactNormalizer::normalizeMax('https://example.com/m'));
        $this->assertSame('my-max-id', VisitorContactNormalizer::normalizeMax('my-max-id'));
    }
}
