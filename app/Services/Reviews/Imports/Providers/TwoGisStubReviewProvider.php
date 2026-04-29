<?php

declare(strict_types=1);

namespace App\Services\Reviews\Imports\Providers;

use App\Models\ReviewImportSource;
use App\Services\Reviews\Imports\Contracts\ExternalReviewProvider;
use App\Services\Reviews\Imports\Dto\ReviewFetchOptions;
use App\Services\Reviews\Imports\Dto\ReviewFetchResult;
use App\Services\Reviews\Imports\Dto\ReviewProviderCapabilities;
use App\Services\Reviews\Imports\ExternalReviewSourceRef;

final class TwoGisStubReviewProvider implements ExternalReviewProvider
{
    public function providerKey(): string
    {
        return 'two_gis';
    }

    public function detect(string $url): bool
    {
        $u = mb_strtolower($url);

        return str_contains($u, '2gis.ru') || str_contains($u, '2gis.com');
    }

    public function parseSourceUrl(string $url): ExternalReviewSourceRef
    {
        return new ExternalReviewSourceRef(normalizedUrl: trim($url));
    }

    public function capabilities(): ReviewProviderCapabilities
    {
        return new ReviewProviderCapabilities(
            canFetchText: false,
            needsAuth: false,
            canFetchAvatar: false,
            canFetchRating: false,
            canFetchDate: false,
            unavailableReason: 'Official 2GIS Places API does not expose review texts; use manual CSV/JSON.',
        );
    }

    public function fetchPreview(ReviewImportSource $source, ReviewFetchOptions $options): ReviewFetchResult
    {
        return ReviewFetchResult::unavailable(
            'unsupported_official_api',
            $this->capabilities()->unavailableReason ?? '2GIS text import unavailable.',
        );
    }
}
