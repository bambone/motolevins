<?php

namespace App\MediaPresentation\Profiles;

use App\MediaPresentation\Contracts\SlotPresentationProfileInterface;
use App\MediaPresentation\FocalPoint;
use App\MediaPresentation\ViewportKey;

final class PromoSlotProfile implements SlotPresentationProfileInterface
{
    public function slotId(): string
    {
        return PromoPresentationProfile::SLOT_ID;
    }

    public function defaultFocalForViewport(ViewportKey $key): FocalPoint
    {
        return PromoPresentationProfile::defaultFocalForViewport($key);
    }

    public function previewFrames(): array
    {
        return PromoPresentationProfile::previewFrames();
    }

    public function viewportKeyForWidth(int $widthPx): ViewportKey
    {
        return PromoPresentationProfile::viewportKeyForWidth($widthPx);
    }

    public function framingScaleMin(): float
    {
        return PromoPresentationProfile::FRAMING_SCALE_MIN;
    }

    public function framingScaleMax(): float
    {
        return PromoPresentationProfile::FRAMING_SCALE_MAX;
    }

    public function framingScaleStep(): float
    {
        return PromoPresentationProfile::FRAMING_SCALE_STEP;
    }

    public function framingScaleDefault(): float
    {
        return PromoPresentationProfile::FRAMING_SCALE_DEFAULT;
    }

    public function safeAreaBottomBand(): array
    {
        return PromoPresentationProfile::safeAreaBottomBand();
    }

    public function articleOverlayCssVariables(): array
    {
        return PromoPresentationProfile::articleOverlayCssVariables();
    }
}
