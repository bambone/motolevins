<?php

namespace App\MediaPresentation\Profiles;

use App\MediaPresentation\Contracts\SlotPresentationProfileInterface;
use App\MediaPresentation\FocalPoint;
use App\MediaPresentation\ViewportKey;

final class TeamCardSlotProfile implements SlotPresentationProfileInterface
{
    public function slotId(): string
    {
        return TeamCardPresentationProfile::SLOT_ID;
    }

    public function defaultFocalForViewport(ViewportKey $key): FocalPoint
    {
        return TeamCardPresentationProfile::defaultFocalForViewport($key);
    }

    public function previewFrames(): array
    {
        return TeamCardPresentationProfile::previewFrames();
    }

    public function viewportKeyForWidth(int $widthPx): ViewportKey
    {
        return TeamCardPresentationProfile::viewportKeyForWidth($widthPx);
    }

    public function framingScaleMin(): float
    {
        return TeamCardPresentationProfile::FRAMING_SCALE_MIN;
    }

    public function framingScaleMax(): float
    {
        return TeamCardPresentationProfile::FRAMING_SCALE_MAX;
    }

    public function framingScaleStep(): float
    {
        return TeamCardPresentationProfile::FRAMING_SCALE_STEP;
    }

    public function framingScaleDefault(): float
    {
        return TeamCardPresentationProfile::FRAMING_SCALE_DEFAULT;
    }

    public function safeAreaBottomBand(): array
    {
        return TeamCardPresentationProfile::safeAreaBottomBand();
    }

    public function articleOverlayCssVariables(): array
    {
        return TeamCardPresentationProfile::articleOverlayCssVariables();
    }
}
