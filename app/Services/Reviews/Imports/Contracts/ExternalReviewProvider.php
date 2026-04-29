<?php

declare(strict_types=1);

namespace App\Services\Reviews\Imports\Contracts;

use App\Models\ReviewImportSource;
use App\Services\Reviews\Imports\Dto\ReviewFetchOptions;
use App\Services\Reviews\Imports\Dto\ReviewFetchResult;
use App\Services\Reviews\Imports\Dto\ReviewProviderCapabilities;
use App\Services\Reviews\Imports\ExternalReviewSourceRef;

interface ExternalReviewProvider
{
    public function providerKey(): string;

    public function detect(string $url): bool;

    public function parseSourceUrl(string $url): ExternalReviewSourceRef;

    public function capabilities(): ReviewProviderCapabilities;

    public function fetchPreview(ReviewImportSource $source, ReviewFetchOptions $options): ReviewFetchResult;
}
