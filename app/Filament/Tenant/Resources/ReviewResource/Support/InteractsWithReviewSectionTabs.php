<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ReviewResource\Support;

use App\Filament\Tenant\Resources\ReviewResource\Pages\ListReviewImportCandidates;
use App\Filament\Tenant\Resources\ReviewResource\Pages\ListReviewImportSources;
use App\Filament\Tenant\Resources\ReviewResource\Pages\ListReviews;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

trait InteractsWithReviewSectionTabs
{
    abstract protected function reviewSectionActiveTab(): string;

    public function getSubheading(): string|Htmlable|null
    {
        return new HtmlString(view('filament.tenant.components.review-section-tabs', [
            'active' => $this->reviewSectionActiveTab(),
            'reviewsUrl' => ListReviews::getUrl(),
            'sourcesUrl' => ListReviewImportSources::getUrl(),
            'candidatesUrl' => ListReviewImportCandidates::getUrl(),
        ])->render());
    }
}
