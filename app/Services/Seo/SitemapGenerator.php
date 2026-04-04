<?php

namespace App\Services\Seo;

use App\Models\Tenant;
use DOMDocument;
use DOMElement;

final class SitemapGenerator
{
    public function __construct(
        private SitemapUrlProvider $urlProvider,
    ) {}

    public function generateXml(Tenant $tenant): string
    {
        $urls = $this->urlProvider->collectEntries($tenant);

        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = false;
        $urlset = $doc->createElementNS('http://www.sitemaps.org/schemas/sitemap/0.9', 'urlset');
        $doc->appendChild($urlset);

        foreach ($urls as $u) {
            $urlEl = $doc->createElement('url');
            $this->appendTextChild($doc, $urlEl, 'loc', $u['loc']);
            if (! empty($u['lastmod'])) {
                $this->appendTextChild($doc, $urlEl, 'lastmod', $u['lastmod']);
            }
            if (! empty($u['changefreq'])) {
                $this->appendTextChild($doc, $urlEl, 'changefreq', $u['changefreq']);
            }
            if (! empty($u['priority'])) {
                $this->appendTextChild($doc, $urlEl, 'priority', $u['priority']);
            }
            $urlset->appendChild($urlEl);
        }

        return $doc->saveXML() ?: '';
    }

    private function appendTextChild(DOMDocument $doc, DOMElement $parent, string $name, string $value): void
    {
        $el = $doc->createElement($name);
        $el->appendChild($doc->createTextNode($value));
        $parent->appendChild($el);
    }
}
