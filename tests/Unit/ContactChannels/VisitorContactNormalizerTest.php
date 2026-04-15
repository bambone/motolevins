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
        yield 'cyrillic_username' => ['иван_иванов', null];
        yield 'cyrillic_in_tme' => ['https://t.me/марат', null];
    }

    #[DataProvider('vkProvider')]
    public function test_normalize_vk(string $raw, ?string $expected): void
    {
        $this->assertSame($expected, VisitorContactNormalizer::normalizeVk($raw));
    }

    /**
     * @return iterable<string, array{0: string, 1: ?string}>
     */
    public static function vkProvider(): iterable
    {
        yield 'username' => ['durov', 'https://vk.com/durov'];
        yield 'https' => ['https://vk.com/id1', 'https://vk.com/id1'];
        yield 'vk_com_no_scheme' => ['vk.com/team', 'https://vk.com/team'];
        yield 'mobile_host' => ['https://m.vk.com/nickname', 'https://vk.com/nickname'];
        yield 'empty' => ['', null];
        yield 'whitespace' => ['   ', null];
        yield 'just_vk' => ['vk', null];
        yield 'just_vk_url' => ['https://vk.com/vk', null];
        yield 'at_only' => ['@', null];
        yield 'foreign_url' => ['https://example.com/user', null];
        yield 'cyrillic_noise' => ['пишите в вк', null];
    }

    public function test_normalize_max_accepts_url_or_string(): void
    {
        $this->assertSame('https://example.com/m', VisitorContactNormalizer::normalizeMax('https://example.com/m'));
        $this->assertSame('my-max-id', VisitorContactNormalizer::normalizeMax('my-max-id'));
    }
}
