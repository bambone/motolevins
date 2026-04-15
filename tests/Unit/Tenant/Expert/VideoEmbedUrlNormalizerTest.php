<?php

namespace Tests\Unit\Tenant\Expert;

use App\Tenant\Expert\VideoEmbedUrlNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class VideoEmbedUrlNormalizerTest extends TestCase
{
    #[DataProvider('youtubeProvider')]
    public function test_youtube(string $share): void
    {
        $src = VideoEmbedUrlNormalizer::toIframeSrc('youtube', $share);
        $this->assertNotNull($src);
        $this->assertStringStartsWith('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', $src);
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function youtubeProvider(): iterable
    {
        yield 'watch' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ'];
        yield 'short' => ['https://youtu.be/dQw4w9WgXcQ'];
        yield 'shorts_path' => ['https://www.youtube.com/shorts/dQw4w9WgXcQ'];
        yield 'embed_path' => ['https://www.youtube.com/embed/dQw4w9WgXcQ'];
        yield 'live_path' => ['https://www.youtube.com/live/dQw4w9WgXcQ'];
    }

    public function test_vk_page_url(): void
    {
        $src = VideoEmbedUrlNormalizer::toIframeSrc('vk', 'https://vk.com/video-231646483_456239036');
        $this->assertNotNull($src);
        $this->assertStringContainsString('vk.com/video_ext.php', $src);
        $this->assertStringContainsString('oid=', $src);
        $this->assertStringContainsString('id=', $src);
    }

    public function test_vk_video_ext_passthrough(): void
    {
        $url = 'https://vk.com/video_ext.php?oid=-231646483&id=456239036';
        $src = VideoEmbedUrlNormalizer::toIframeSrc('vk', $url);
        $this->assertNotNull($src);
        $this->assertStringContainsString('video_ext.php', $src);
    }

    public function test_rejects_raw_iframe_html(): void
    {
        $this->assertNull(VideoEmbedUrlNormalizer::toIframeSrc('vk', '<iframe src="https://vk.com/"></iframe>'));
    }

    public function test_unknown_provider(): void
    {
        $this->assertNull(VideoEmbedUrlNormalizer::toIframeSrc('vimeo', 'https://vimeo.com/123'));
    }

    public function test_vk_rejects_non_vk_host(): void
    {
        $this->assertNull(VideoEmbedUrlNormalizer::toIframeSrc('vk', 'https://evil.test/https://vk.com/video-1_2'));
    }

    public function test_vk_accepts_www_host(): void
    {
        $src = VideoEmbedUrlNormalizer::toIframeSrc('vk', 'https://www.vk.com/video-1_2');
        $this->assertNotNull($src);
        $this->assertStringContainsString('video_ext.php', $src);
    }
}
