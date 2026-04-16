<?php

namespace App\MediaPresentation\Profiles;

use App\MediaPresentation\Contracts\SlotPresentationProfileInterface;
use App\MediaPresentation\FocalPoint;
use App\MediaPresentation\ViewportKey;

final class ArticleCoverSlotProfile implements SlotPresentationProfileInterface
{
    public function slotId(): string
    {
        return ArticleCoverPresentationProfile::SLOT_ID;
    }

    public function defaultFocalForViewport(ViewportKey $key): FocalPoint
    {
        return ArticleCoverPresentationProfile::defaultFocalForViewport($key);
    }

    public function previewFrames(): array
    {
        return ArticleCoverPresentationProfile::previewFrames();
    }

    public function viewportKeyForWidth(int $widthPx): ViewportKey
    {
        return ArticleCoverPresentationProfile::viewportKeyForWidth($widthPx);
    }

    public function framingScaleMin(): float
    {
        return ArticleCoverPresentationProfile::FRAMING_SCALE_MIN;
    }

    public function framingScaleMax(): float
    {
        return ArticleCoverPresentationProfile::FRAMING_SCALE_MAX;
    }

    public function framingScaleStep(): float
    {
        return ArticleCoverPresentationProfile::FRAMING_SCALE_STEP;
    }

    public function framingScaleDefault(): float
    {
        return ArticleCoverPresentationProfile::FRAMING_SCALE_DEFAULT;
    }

    public function safeAreaBottomBand(): array
    {
        return ArticleCoverPresentationProfile::safeAreaBottomBand();
    }

    public function articleOverlayCssVariables(): array
    {
        return ArticleCoverPresentationProfile::articleOverlayCssVariables();
    }
}
