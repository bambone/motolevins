<?php

declare(strict_types=1);

namespace App\Services\Reviews\Imports\Dto;

final class ReviewFetchOptions
{
    public function __construct(
        public readonly int $minTextLength = 30,
        public readonly int $maxPerRun = 100,
        public readonly int $pageSize = 100,
        public readonly int $maxPages = 5,
    ) {}
}
