<?php

namespace Tests\Unit\Rules;

use App\Rules\PublicHeroVideoAssetReference;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PublicHeroVideoAssetReferenceTest extends TestCase
{
    public function test_accepts_site_videos_path(): void
    {
        $v = Validator::make(
            ['x' => 'site/videos/Moto_levins_1.mp4'],
            ['x' => [new PublicHeroVideoAssetReference]],
        );
        $this->assertFalse($v->fails());
    }

    public function test_accepts_legacy_images_motolevins_videos(): void
    {
        $v = Validator::make(
            ['x' => 'images/motolevins/videos/Moto_levins_1.mp4'],
            ['x' => [new PublicHeroVideoAssetReference]],
        );
        $this->assertFalse($v->fails());
    }

    public function test_rejects_random_string(): void
    {
        $v = Validator::make(
            ['x' => 'not-a-video-path'],
            ['x' => [new PublicHeroVideoAssetReference]],
        );
        $this->assertTrue($v->fails());
    }
}
