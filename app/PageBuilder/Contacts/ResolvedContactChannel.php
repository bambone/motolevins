<?php

namespace App\PageBuilder\Contacts;

/**
 * Usable channel ready for public render (single link/card).
 */
final readonly class ResolvedContactChannel
{
    public function __construct(
        public ContactChannelType $type,
        public string $href,
        public string $displayValue,
        public string $ctaLabel,
        public bool $openInNewTab,
        public ?string $rel,
        public string $icon,
        public ?string $note,
    ) {}
}
