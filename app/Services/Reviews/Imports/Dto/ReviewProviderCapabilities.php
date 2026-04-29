<?php

declare(strict_types=1);

namespace App\Services\Reviews\Imports\Dto;

final class ReviewProviderCapabilities
{
    public function __construct(
        public readonly bool $canFetchText,
        public readonly bool $needsAuth,
        public readonly bool $canFetchAvatar,
        public readonly bool $canFetchRating,
        public readonly bool $canFetchDate,
        public readonly ?string $unavailableReason = null,
    ) {}
}
