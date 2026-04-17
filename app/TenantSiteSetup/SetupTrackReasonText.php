<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

/**
 * Человекочитаемое объяснение кода причины из {@see SetupTracksResolver} / UI.
 */
final readonly class SetupTrackReasonText
{
    public function __construct(
        public string $title,
        public string $body,
        public ?string $actionHint = null,
    ) {}
}
