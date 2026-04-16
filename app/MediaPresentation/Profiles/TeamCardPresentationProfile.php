<?php

namespace App\MediaPresentation\Profiles;

use App\MediaPresentation\FocalPoint;
use App\MediaPresentation\ViewportKey;

/** Slot: {@code team_card} — portrait / team grid cards. */
final class TeamCardPresentationProfile
{
    public const SLOT_ID = 'team_card';

    public const PICTURE_MOBILE_MAX_PX = 1023;

    public const FRAMING_SCALE_MIN = 1.0;

    public const FRAMING_SCALE_MAX = 1.5;

    public const FRAMING_SCALE_STEP = 0.05;

    public const FRAMING_SCALE_DEFAULT = 1.0;

    public static function defaultFocalForViewport(ViewportKey $key): FocalPoint
    {
        return match ($key) {
            ViewportKey::Mobile => FocalPoint::normalized(50.0, 38.0),
            ViewportKey::Tablet => FocalPoint::normalized(50.0, 40.0),
            ViewportKey::Desktop => FocalPoint::normalized(50.0, 40.0),
            ViewportKey::Default => FocalPoint::center(),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function articleOverlayCssVariables(): array
    {
        return [];
    }

    /**
     * @return list<array{key: string, label: string, width: int, height: int, maxCssPx: int}>
     */
    public static function previewFrames(): array
    {
        return [
            ['key' => 'mobile', 'label' => 'Mobile', 'width' => 320, 'height' => 400, 'maxCssPx' => 639],
            ['key' => 'tablet', 'label' => 'Tablet', 'width' => 400, 'height' => 480, 'maxCssPx' => 1023],
            ['key' => 'desktop', 'label' => 'Desktop', 'width' => 480, 'height' => 560, 'maxCssPx' => 9999],
        ];
    }

    public static function viewportKeyForWidth(int $widthPx): ViewportKey
    {
        return $widthPx <= self::PICTURE_MOBILE_MAX_PX ? ViewportKey::Mobile : ViewportKey::Desktop;
    }

    /**
     * @return array{bottomPercent: float, label: string}
     */
    public static function safeAreaBottomBand(): array
    {
        return ['bottomPercent' => 22.0, 'label' => 'Имя / роль'];
    }
}
