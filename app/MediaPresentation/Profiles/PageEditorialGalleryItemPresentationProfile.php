<?php

namespace App\MediaPresentation\Profiles;

use App\MediaPresentation\Contracts\SlotPresentationProfileInterface;
use App\MediaPresentation\FocalPoint;
use App\MediaPresentation\ViewportKey;

/**
 * Slot: {@code page_editorial_gallery_item} — кадры ячеек сетки Expert «Галерея» (фиксированная рамка + crop между вьюпортами).
 *
 * Реестр подключает {@see PageEditorialGalleryItemSlotProfile} (реализует {@see SlotPresentationProfileInterface});
 * этот класс — те же константы и статические хелперы, что {@see ServiceProgramCardPresentationProfile} для programs.
 *
 * Tablet: только для превью в админке и fallback в {@code viewport_focal_map}; на сайте — {@see ViewportKey::Mobile} или {@see ViewportKey::Desktop}.
 */
final class PageEditorialGalleryItemPresentationProfile
{
    public const SLOT_ID = 'page_editorial_gallery_item';

    public const PICTURE_MOBILE_MAX_PX = 1023;

    public const FRAMING_SCALE_MIN = 1.0;

    public const FRAMING_SCALE_MAX = 1.5;

    public const FRAMING_SCALE_STEP = 0.05;

    public const FRAMING_SCALE_DEFAULT = 1.0;

    public static function defaultFocalForViewport(ViewportKey $key): FocalPoint
    {
        return match ($key) {
            ViewportKey::Mobile => FocalPoint::normalized(50.0, 45.0),
            ViewportKey::Tablet => FocalPoint::normalized(50.0, 48.0),
            ViewportKey::Desktop => FocalPoint::normalized(50.0, 48.0),
            ViewportKey::Default => FocalPoint::center(),
        };
    }

    /**
     * Оверлеи для публичного inline style (MVP: нет — превью в Filament берёт дефолтные маски из {@see FramingCoverFocalEditor}).
     *
     * @return array<string, string>
     */
    public static function articleOverlayCssVariables(): array
    {
        return [];
    }

    /**
     * Рамки превью в админке (соотношения близки к сетке галереи: 4:3 / герой-клетка).
     *
     * @return list<array{key: string, label: string, width: int, height: int, maxCssPx: int}>
     */
    public static function previewFrames(): array
    {
        return [
            [
                'key' => 'mobile',
                'label' => 'Mobile',
                'width' => 360,
                'height' => 270,
                'maxCssPx' => 639,
            ],
            [
                'key' => 'tablet',
                'label' => 'Tablet',
                'width' => 600,
                'height' => 450,
                'maxCssPx' => 1023,
            ],
            [
                'key' => 'desktop',
                'label' => 'Desktop',
                'width' => 800,
                'height' => 600,
                'maxCssPx' => 9999,
            ],
        ];
    }

    public static function viewportKeyForWidth(int $widthPx): ViewportKey
    {
        if ($widthPx <= self::PICTURE_MOBILE_MAX_PX) {
            return ViewportKey::Mobile;
        }

        return ViewportKey::Desktop;
    }

    /**
     * @return array{bottomPercent: float, label: string}
     */
    public static function safeAreaBottomBand(): array
    {
        return [
            'bottomPercent' => 28.0,
            'label' => 'Подпись',
        ];
    }
}
