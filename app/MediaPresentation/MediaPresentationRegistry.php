<?php

namespace App\MediaPresentation;

use App\MediaPresentation\Contracts\SlotPresentationProfileInterface;
use App\MediaPresentation\Profiles\ArticleCoverPresentationProfile;
use App\MediaPresentation\Profiles\ArticleCoverSlotProfile;
use App\MediaPresentation\Profiles\PageEditorialGalleryItemPresentationProfile;
use App\MediaPresentation\Profiles\PageEditorialGalleryItemSlotProfile;
use App\MediaPresentation\Profiles\PageHeroCoverPresentationProfile;
use App\MediaPresentation\Profiles\PageHeroCoverSlotProfile;
use App\MediaPresentation\Profiles\PromoPresentationProfile;
use App\MediaPresentation\Profiles\PromoSlotProfile;
use App\MediaPresentation\Profiles\ServiceProgramCardPresentationProfile;
use App\MediaPresentation\Profiles\ServiceProgramCardSlotProfile;
use App\MediaPresentation\Profiles\TeamCardPresentationProfile;
use App\MediaPresentation\Profiles\TeamCardSlotProfile;
use InvalidArgumentException;

/**
 * Resolves presentation profile instances by slot id (platform extension point).
 */
final class MediaPresentationRegistry
{
    /** @var array<string, class-string<SlotPresentationProfileInterface>> */
    private const PROFILE_CLASSES = [
        ServiceProgramCardPresentationProfile::SLOT_ID => ServiceProgramCardSlotProfile::class,
        PageHeroCoverPresentationProfile::SLOT_ID => PageHeroCoverSlotProfile::class,
        PageEditorialGalleryItemPresentationProfile::SLOT_ID => PageEditorialGalleryItemSlotProfile::class,
        ArticleCoverPresentationProfile::SLOT_ID => ArticleCoverSlotProfile::class,
        TeamCardPresentationProfile::SLOT_ID => TeamCardSlotProfile::class,
        PromoPresentationProfile::SLOT_ID => PromoSlotProfile::class,
    ];

    public static function profile(string $slotId): SlotPresentationProfileInterface
    {
        $class = self::PROFILE_CLASSES[$slotId] ?? null;
        if ($class === null) {
            throw new InvalidArgumentException('Unknown media presentation slot: '.$slotId);
        }

        return app($class);
    }

    public static function defaultFocalForSlot(string $slotId, ViewportKey $key): FocalPoint
    {
        if (! self::slotExists($slotId)) {
            return FocalPoint::center();
        }

        return self::profile($slotId)->defaultFocalForViewport($key);
    }

    /**
     * @return list<string>
     */
    public static function registeredSlotIds(): array
    {
        return array_keys(self::PROFILE_CLASSES);
    }

    public static function slotExists(string $slotId): bool
    {
        return isset(self::PROFILE_CLASSES[$slotId]);
    }
}
