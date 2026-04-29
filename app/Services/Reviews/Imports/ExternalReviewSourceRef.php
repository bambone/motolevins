<?php

declare(strict_types=1);

namespace App\Services\Reviews\Imports;

final class ExternalReviewSourceRef
{
    public function __construct(
        public readonly string $normalizedUrl,
        public readonly ?string $externalOwnerId = null,
        public readonly ?string $externalTopicId = null,
        public readonly ?string $externalPlaceId = null,
    ) {}

    public function fingerprint(): string
    {
        return hash('sha256', implode('|', array_filter([
            $this->normalizedUrl,
            $this->externalOwnerId,
            $this->externalTopicId,
            $this->externalPlaceId,
        ], fn ($v) => $v !== null && $v !== '')));
    }
}
