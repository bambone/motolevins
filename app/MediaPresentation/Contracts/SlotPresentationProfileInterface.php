<?php

namespace App\MediaPresentation\Contracts;

use App\MediaPresentation\FocalPoint;
use App\MediaPresentation\MediaPresentationRegistry;
use App\MediaPresentation\ViewportKey;

/**
 * Profile-driven behavior for a content image slot (builder, programs, etc.).
 * Implementations are registered in {@see MediaPresentationRegistry}.
 */
interface SlotPresentationProfileInterface
{
    public function slotId(): string;

    public function defaultFocalForViewport(ViewportKey $key): FocalPoint;

    /**
     * @return list<array{key: string, label: string, width: int, height: int, maxCssPx: int}>
     */
    public function previewFrames(): array;

    public function viewportKeyForWidth(int $widthPx): ViewportKey;

    public function framingScaleMin(): float;

    public function framingScaleMax(): float;

    public function framingScaleStep(): float;

    public function framingScaleDefault(): float;

    /**
     * @return array{bottomPercent: float, label: string}
     */
    public function safeAreaBottomBand(): array;

    /**
     * Overlay CSS variable names (without leading --) for public inline style; mobile/desktop suffixes as in programs.
     *
     * @return array<string, string>
     */
    public function articleOverlayCssVariables(): array;
}
