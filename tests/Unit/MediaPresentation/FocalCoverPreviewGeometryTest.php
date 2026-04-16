<?php

namespace Tests\Unit\MediaPresentation;

use App\MediaPresentation\FocalCoverPreviewGeometry;
use PHPUnit\Framework\TestCase;

final class FocalCoverPreviewGeometryTest extends TestCase
{
    public function test_center_focal_zero_translate(): void
    {
        $t = FocalCoverPreviewGeometry::translateFromFocal(50, 50, 360, 264, 1200, 800);
        $this->assertEqualsWithDelta(0.0, $t['tx'], 0.0001);
        $this->assertEqualsWithDelta(0.0, $t['ty'], 0.0001);
    }

    public function test_round_trip_focal_translate_horizontal_axis(): void
    {
        $iw = 1200;
        $ih = 800;
        $w = 360.0;
        $h = 264.0;
        // For this aspect combo, height limits scale → dispH === frameH; vertical focal stays 50%.
        foreach ([30.0, 50.0, 72.5] as $px) {
            $tr = FocalCoverPreviewGeometry::translateFromFocal($px, 50.0, $w, $h, $iw, $ih);
            $back = FocalCoverPreviewGeometry::focalFromTranslate($tr['tx'], $tr['ty'], $w, $h, $iw, $ih);
            $this->assertEqualsWithDelta($px, $back['x'], 0.05, "px={$px}");
            $this->assertEqualsWithDelta(50.0, $back['y'], 0.05);
        }
    }

    public function test_focal_for_commit_one_decimal(): void
    {
        $c = FocalCoverPreviewGeometry::focalForCommit(18.25, 52.44);
        $this->assertSame(18.3, $c['x']);
        $this->assertSame(52.4, $c['y']);
    }

    public function test_round_trip_focal_translate_with_user_scale(): void
    {
        $iw = 1200;
        $ih = 800;
        $w = 360.0;
        $h = 264.0;
        $us = 1.2;
        foreach ([30.0, 50.0, 72.5] as $px) {
            $tr = FocalCoverPreviewGeometry::translateFromFocal($px, 50.0, $w, $h, $iw, $ih, $us);
            $back = FocalCoverPreviewGeometry::focalFromTranslate($tr['tx'], $tr['ty'], $w, $h, $iw, $ih, $us);
            $this->assertEqualsWithDelta($px, $back['x'], 0.05, "px={$px}");
            $this->assertEqualsWithDelta(50.0, $back['y'], 0.05);
        }
    }
}
