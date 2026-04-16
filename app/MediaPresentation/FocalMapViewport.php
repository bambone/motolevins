<?php

namespace App\MediaPresentation;

/**
 * Pick focal from {@code viewport_focal_map} using the same key order as {@see ServiceProgramCardPresentationResolver}.
 */
final class FocalMapViewport
{
    /**
     * @param  array<string, array{x: float, y: float}>  $map
     */
    public static function pickFocalFromMap(array $map, ViewportKey $viewport): ?FocalPoint
    {
        $order = match ($viewport) {
            ViewportKey::Tablet => ['tablet', 'mobile', 'default'],
            ViewportKey::Mobile => ['mobile', 'default'],
            ViewportKey::Desktop => ['desktop', 'default'],
            ViewportKey::Default => ['default'],
        };
        foreach ($order as $k) {
            if (! isset($map[$k])) {
                continue;
            }
            $fp = FocalPoint::tryFromArray($map[$k]);
            if ($fp !== null) {
                return $fp;
            }
        }

        return null;
    }
}
