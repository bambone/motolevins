<?php

declare(strict_types=1);

namespace App\Services\Reviews\Imports\Providers;

use App\Models\ReviewImportSource;
use App\Services\Reviews\Imports\Contracts\ExternalReviewProvider;
use App\Services\Reviews\Imports\Dto\ReviewFetchOptions;
use App\Services\Reviews\Imports\Dto\ReviewFetchResult;
use App\Services\Reviews\Imports\Dto\ReviewProviderCapabilities;
use App\Services\Reviews\Imports\ExternalReviewSourceRef;

final class YandexStubReviewProvider implements ExternalReviewProvider
{
    public function providerKey(): string
    {
        return 'yandex_maps';
    }

    public function detect(string $url): bool
    {
        $u = mb_strtolower($url);

        return str_contains($u, 'yandex.ru/maps') || str_contains($u, 'yandex.com/maps');
    }

    public function parseSourceUrl(string $url): ExternalReviewSourceRef
    {
        return new ExternalReviewSourceRef(normalizedUrl: trim($url));
    }

    public function capabilities(): ReviewProviderCapabilities
    {
        return new ReviewProviderCapabilities(
            canFetchText: false,
            needsAuth: true,
            canFetchAvatar: false,
            canFetchRating: false,
            canFetchDate: false,
            unavailableReason: 'Yandex Maps review texts require an official data channel; use manual CSV/JSON.',
        );
    }

    public function fetchPreview(ReviewImportSource $source, ReviewFetchOptions $options): ReviewFetchResult
    {
        return ReviewFetchResult::unavailable(
            'needs_official_access',
            $this->capabilities()->unavailableReason ?? 'Yandex import unavailable.',
        );
    }
}
