<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\FilamentTipTapDocumentSanitizer;
use PHPUnit\Framework\TestCase;

final class FilamentTipTapDocumentSanitizerTest extends TestCase
{
    public function test_passes_through_string_html(): void
    {
        $html = '<p>Hello</p>';
        $this->assertSame($html, FilamentTipTapDocumentSanitizer::sanitizeLivewireState($html));
    }

    public function test_passes_through_valid_empty_doc(): void
    {
        $doc = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'attrs' => ['textAlign' => 'start'],
                    'content' => [],
                ],
            ],
        ];
        $this->assertSame($doc, FilamentTipTapDocumentSanitizer::sanitizeLivewireState($doc));
    }

    public function test_replaces_corrupt_doc_with_empty_doc(): void
    {
        $inner = ['type' => 'paragraph', 'attrs' => ['textAlign' => 'start'], 'content' => []];
        for ($i = 0; $i < 50; $i++) {
            $inner = ['type' => 'paragraph', 'attrs' => ['textAlign' => 'start'], 'content' => [$inner]];
        }
        $corrupt = [
            'type' => 'doc',
            'content' => [$inner],
        ];
        $out = FilamentTipTapDocumentSanitizer::sanitizeLivewireState($corrupt);
        $this->assertSame('doc', $out['type']);
        $this->assertArrayHasKey('content', $out);
        $this->assertSame('paragraph', $out['content'][0]['type'] ?? null);
    }

    public function test_sanitizes_first_element_of_livewire_tuple(): void
    {
        $bad = [
            [
                'type' => 'doc',
                'content' => [
                    ['type' => 'paragraph', 'content' => [['not' => 'a node']]],
                ],
            ],
            [],
        ];
        $out = FilamentTipTapDocumentSanitizer::sanitizeLivewireState($bad);
        $this->assertIsList($out);
        $this->assertSame('doc', $out[0]['type']);
        $this->assertSame([], $out[1]);
    }

    public function test_replaces_doc_with_list_shaped_attrs_on_paragraph(): void
    {
        $corrupt = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'attrs' => [
                        ['textAlign' => 'start'],
                        ['s' => 'arr'],
                    ],
                    'content' => [],
                ],
            ],
        ];
        $out = FilamentTipTapDocumentSanitizer::sanitizeLivewireState($corrupt);
        $this->assertSame('doc', $out['type']);
        $this->assertSame('paragraph', $out['content'][0]['type'] ?? null);
        $this->assertSame('start', $out['content'][0]['attrs']['textAlign'] ?? null);
    }
}
