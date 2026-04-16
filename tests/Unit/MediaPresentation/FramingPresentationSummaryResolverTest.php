<?php

namespace Tests\Unit\MediaPresentation;

use App\MediaPresentation\FramingPresentationSummaryResolver;
use App\MediaPresentation\MediaPresentationRegistry;
use App\MediaPresentation\PresentationData;
use App\MediaPresentation\Profiles\PageHeroCoverPresentationProfile;
use Tests\TestCase;

class FramingPresentationSummaryResolverTest extends TestCase
{
    public function test_empty_presentation_is_default_label(): void
    {
        $r = app(FramingPresentationSummaryResolver::class);
        $profile = MediaPresentationRegistry::profile(PageHeroCoverPresentationProfile::SLOT_ID);
        $out = $r->summarize(PresentationData::empty()->toArray(), $profile);

        $this->assertStringContainsString('умолчан', $out['label']);
    }
}
