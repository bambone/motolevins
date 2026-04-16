<?php

namespace App\MediaPresentation\Profiles;

use App\MediaPresentation\Contracts\SlotPresentationProfileInterface;
use App\MediaPresentation\FocalPoint;
use App\MediaPresentation\ViewportKey;

/**
 * Adapter: {@see ServiceProgramCardPresentationProfile} static API → {@see SlotPresentationProfileInterface}.
 */
final class ServiceProgramCardSlotProfile implements SlotPresentationProfileInterface
{
    public function slotId(): string
    {
        return ServiceProgramCardPresentationProfile::SLOT_ID;
    }

    public function defaultFocalForViewport(ViewportKey $key): FocalPoint
    {
        return ServiceProgramCardPresentationProfile::defaultFocalForViewport($key);
    }

    public function previewFrames(): array
    {
        return ServiceProgramCardPresentationProfile::previewFrames();
    }

    public function viewportKeyForWidth(int $widthPx): ViewportKey
    {
        return ServiceProgramCardPresentationProfile::viewportKeyForWidth($widthPx);
    }

    public function framingScaleMin(): float
    {
        return ServiceProgramCardPresentationProfile::FRAMING_SCALE_MIN;
    }

    public function framingScaleMax(): float
    {
        return ServiceProgramCardPresentationProfile::FRAMING_SCALE_MAX;
    }

    public function framingScaleStep(): float
    {
        return ServiceProgramCardPresentationProfile::FRAMING_SCALE_STEP;
    }

    public function framingScaleDefault(): float
    {
        return ServiceProgramCardPresentationProfile::FRAMING_SCALE_DEFAULT;
    }

    public function safeAreaBottomBand(): array
    {
        return ServiceProgramCardPresentationProfile::safeAreaBottomBand();
    }

    public function articleOverlayCssVariables(): array
    {
        return ServiceProgramCardPresentationProfile::articleOverlayCssVariables();
    }
}
