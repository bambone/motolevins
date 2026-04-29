<?php

declare(strict_types=1);

namespace App\Services\Reviews\Imports\Dto;

use DateTimeInterface;

final class ExternalReviewItemDto
{
    /**
     * @param  array<string, mixed>  $media
     * @param  array<string, mixed>  $rawPayload
     */
    public function __construct(
        public readonly ?string $externalId,
        public readonly ?string $authorName,
        public readonly ?string $authorAvatarUrl,
        public readonly ?int $rating,
        public readonly ?DateTimeInterface $reviewedAt,
        public readonly string $body,
        public readonly ?string $sourceUrl,
        public readonly array $media = [],
        public readonly array $rawPayload = [],
    ) {}
}
