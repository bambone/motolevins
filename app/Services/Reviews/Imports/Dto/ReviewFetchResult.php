<?php

declare(strict_types=1);

namespace App\Services\Reviews\Imports\Dto;

final class ReviewFetchResult
{
    /**
     * @param  list<ExternalReviewItemDto>  $items
     */
    public function __construct(
        public readonly bool $ok,
        public readonly array $items = [],
        public readonly int $fetchedCount = 0,
        public readonly int $duplicateCount = 0,
        public readonly int $errorCount = 0,
        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null,
    ) {}

    public static function unavailable(string $code, string $message): self
    {
        return new self(ok: false, errorCode: $code, errorMessage: $message);
    }

    /**
     * @param  list<ExternalReviewItemDto>  $items
     */
    public static function success(array $items, int $fetched, int $dupes = 0): self
    {
        return new self(ok: true, items: $items, fetchedCount: $fetched, duplicateCount: $dupes);
    }
}
