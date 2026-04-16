<?php

namespace App\MediaPresentation\Profiles;

use App\MediaPresentation\Contracts\SlotPresentationProfileInterface;
use App\MediaPresentation\FocalPoint;
use App\MediaPresentation\ViewportKey;

final class PageEditorialGalleryItemSlotProfile implements SlotPresentationProfileInterface
{
    public function slotId(): string
    {
        return PageEditorialGalleryItemPresentationProfile::SLOT_ID;
    }

    public function defaultFocalForViewport(ViewportKey $key): FocalPoint
    {
        return PageEditorialGalleryItemPresentationProfile::defaultFocalForViewport($key);
    }

    public function previewFrames(): array
    {
        return PageEditorialGalleryItemPresentationProfile::previewFrames();
    }

    public function viewportKeyForWidth(int $widthPx): ViewportKey
    {
        return PageEditorialGalleryItemPresentationProfile::viewportKeyForWidth($widthPx);
    }

    public function framingScaleMin(): float
    {
        return PageEditorialGalleryItemPresentationProfile::FRAMING_SCALE_MIN;
    }

    public function framingScaleMax(): float
    {
        return PageEditorialGalleryItemPresentationProfile::FRAMING_SCALE_MAX;
    }

    public function framingScaleStep(): float
    {
        return PageEditorialGalleryItemPresentationProfile::FRAMING_SCALE_STEP;
    }

    public function framingScaleDefault(): float
    {
        return PageEditorialGalleryItemPresentationProfile::FRAMING_SCALE_DEFAULT;
    }

    public function safeAreaBottomBand(): array
    {
        return PageEditorialGalleryItemPresentationProfile::safeAreaBottomBand();
    }

    public function articleOverlayCssVariables(): array
    {
        return PageEditorialGalleryItemPresentationProfile::articleOverlayCssVariables();
    }
}
