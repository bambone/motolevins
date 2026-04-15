<?php

namespace Tests\Unit\Rules;

use App\Rules\EditorialGalleryAssetUrlRule;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use Validator;

final class EditorialGalleryAssetUrlRuleTest extends TestCase
{
    #[DataProvider('imageOk')]
    public function test_image_accepts_storage_paths(string $v): void
    {
        $v = Validator::make(['u' => $v], ['u' => [new EditorialGalleryAssetUrlRule(EditorialGalleryAssetUrlRule::KIND_IMAGE)]]);
        $this->assertTrue($v->passes());
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function imageOk(): iterable
    {
        yield 'site path' => ['site/brand/gallery-1.jpg'];
        yield 'storage path' => ['storage/tenant/1/public/x.png'];
        yield 'absolute' => ['https://cdn.example.com/a/b/image.webp'];
    }

    public function test_image_rejects_article_url(): void
    {
        $v = Validator::make(['u' => 'https://example.com/news/article-slug'], ['u' => [new EditorialGalleryAssetUrlRule(EditorialGalleryAssetUrlRule::KIND_IMAGE)]]);
        $this->assertFalse($v->passes());
    }

    public function test_video_rejects_vk_watch_page(): void
    {
        $v = Validator::make(['u' => 'https://vk.com/video-1_2'], ['u' => [new EditorialGalleryAssetUrlRule(EditorialGalleryAssetUrlRule::KIND_VIDEO_FILE)]]);
        $this->assertFalse($v->passes());
    }

    public function test_video_accepts_mp4(): void
    {
        $v = Validator::make(['u' => 'site/brand/intro.mp4'], ['u' => [new EditorialGalleryAssetUrlRule(EditorialGalleryAssetUrlRule::KIND_VIDEO_FILE)]]);
        $this->assertTrue($v->passes());
    }
}
