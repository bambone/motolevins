<?php

namespace App\Services\Seo;

/**
 * @phpstan-type JsonLdGraph list<array<string, mixed>>
 * @phpstan-type BreadcrumbCrumb array{name: string, url: string}
 */
final class SeoResolvedData
{
    /**
     * @param  JsonLdGraph  $jsonLd
     * @param  list<BreadcrumbCrumb>  $breadcrumbs
     */
    public function __construct(
        public string $title,
        public string $description,
        public ?string $h1,
        public string $canonical,
        public string $ogTitle,
        public string $ogDescription,
        public string $ogUrl,
        public string $ogSiteName,
        public string $robots,
        public array $jsonLd = [],
        public ?string $metaKeywords = null,
        public ?string $ogImage = null,
        public string $ogType = 'website',
        public string $twitterCard = 'summary_large_image',
        public bool $isIndexable = true,
        public bool $isFollowable = true,
        public array $breadcrumbs = [],
    ) {}
}
