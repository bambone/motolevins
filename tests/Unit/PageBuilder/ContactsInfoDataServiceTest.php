<?php

namespace Tests\Unit\PageBuilder;

use App\PageBuilder\Contacts\ContactsInfoDataService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ContactsInfoDataServiceTest extends TestCase
{
    #[Test]
    public function normalize_unwraps_single_wrapper_of_row_maps(): void
    {
        $bad = [
            [
                '0' => ['type' => 'phone', 'value' => '1'],
                '1' => ['type' => 'email', 'value' => 'a@b.c'],
            ],
        ];
        $fixed = ContactsInfoDataService::normalizeChannelsForRepeater($bad);
        self::assertCount(2, $fixed);
        self::assertSame('phone', $fixed[0]['type']);
        self::assertSame('email', $fixed[1]['type']);
    }

    #[Test]
    public function merge_preserves_channel_list_without_recursive_collapse(): void
    {
        $defaults = [
            'title' => 'Контакты',
            'channels' => [],
        ];
        $existing = [
            'channels' => [
                ['type' => 'phone', 'value' => 'x'],
            ],
        ];
        $merged = ContactsInfoDataService::mergeDataJsonPreservingChannelList($defaults, $existing);
        self::assertCount(1, $merged['channels']);
        self::assertSame('phone', $merged['channels'][0]['type']);
    }
}
