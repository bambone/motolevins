<?php

namespace App\MediaPresentation;

use App\MediaPresentation\Profiles\ServiceProgramCardPresentationProfile;

/**
 * Per-viewport framing for a presentation slot: pan (x/y %) + user zoom (scale ≥ 1).
 *
 * Not to be confused with {@see FocalPoint} (percent point only).
 */
final readonly class ViewportFraming implements \JsonSerializable
{
    public function __construct(
        public float $x,
        public float $y,
        public float $scale,
    ) {}

    /**
     * @param  array<string, mixed>|null  $row  v1: {x,y} only; v2: {x,y,scale}
     */
    public static function fromArray(?array $row): ?self
    {
        if ($row === null) {
            return null;
        }
        $fp = FocalPoint::tryFromArray($row);
        if ($fp === null) {
            return null;
        }
        $scale = isset($row['scale']) && is_numeric($row['scale'])
            ? (float) $row['scale']
            : ServiceProgramCardPresentationProfile::FRAMING_SCALE_DEFAULT;

        return self::normalized($fp->x, $fp->y, $scale);
    }

    public static function normalized(float $x, float $y, float $scale): self
    {
        $p = FocalPoint::normalized($x, $y);
        $s = self::clampScale($scale);

        return new self($p->x, $p->y, $s);
    }

    public static function clampScale(float $scale): float
    {
        $min = ServiceProgramCardPresentationProfile::FRAMING_SCALE_MIN;
        $max = ServiceProgramCardPresentationProfile::FRAMING_SCALE_MAX;
        $s = max($min, min($max, $scale));
        $step = ServiceProgramCardPresentationProfile::FRAMING_SCALE_STEP;

        return round($s / $step) * $step;
    }

    /**
     * Round scale for persisted storage (aligned with step).
     */
    public static function scaleForCommit(float $scale): float
    {
        return self::clampScale($scale);
    }

    public function toFocalPoint(): FocalPoint
    {
        return FocalPoint::normalized($this->x, $this->y);
    }

    /**
     * @return array{x: float, y: float, scale: float}
     */
    public function toArray(): array
    {
        return [
            'x' => $this->x,
            'y' => $this->y,
            'scale' => $this->scale,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
