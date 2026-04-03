<?php

namespace Tests\Support;

use DOMXPath;
use PHPUnit\Framework\Assert;

trait AssertsHtmlMarkerOrder
{
    /**
     * @param  list<string>  $markers
     */
    protected function assertSubstringsAppearInOrder(string $html, array $markers): void
    {
        $lastPos = -1;
        foreach ($markers as $marker) {
            $pos = strpos($html, $marker);
            Assert::assertNotFalse($pos, 'Marker not found in HTML: '.$marker);
            Assert::assertGreaterThan($lastPos, $pos, 'Marker appears out of order: '.$marker);
            $lastPos = $pos;
        }
    }

    /**
     * Asserts a single root element with data-page-section-type contains the marker and non-empty text.
     */
    protected function assertPageSectionWrapperContainsMarker(string $html, string $sectionType, string $marker): void
    {
        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $wrapped = '<?xml encoding="UTF-8"><html><body>'.$html.'</body></html>';
        $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query('//*[@data-page-section-type="'.$sectionType.'"]');
        Assert::assertNotFalse($nodes);
        Assert::assertSame(1, $nodes->length, 'Expected exactly one wrapper for section type: '.$sectionType);

        $node = $nodes->item(0);
        Assert::assertNotNull($node);
        $fragment = $dom->saveHTML($node);
        Assert::assertIsString($fragment);
        Assert::assertStringContainsString($marker, $fragment, 'Marker not inside section wrapper: '.$sectionType);

        $text = trim($node->textContent ?? '');
        Assert::assertNotSame('', $text, 'Section wrapper has empty text content: '.$sectionType);
    }

    protected function assertMainMarkerBeforeFirstExtraSectionWrapper(string $html, string $mainMarker): void
    {
        $mainPos = strpos($html, $mainMarker);
        Assert::assertNotFalse($mainPos, 'Main marker not found');

        $firstExtra = strpos($html, 'data-page-section-type="');
        Assert::assertNotFalse($firstExtra, 'No extra section wrapper in HTML');

        Assert::assertLessThan($firstExtra, $mainPos, 'Main marker must appear before first data-page-section-type wrapper');
    }
}
