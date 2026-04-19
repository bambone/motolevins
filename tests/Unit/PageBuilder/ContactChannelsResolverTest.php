<?php

namespace Tests\Unit\PageBuilder;

use App\PageBuilder\Contacts\ContactChannelRegistry;
use App\PageBuilder\Contacts\ContactChannelsResolver;
use App\PageBuilder\Contacts\ContactChannelType;
use PHPUnit\Framework\TestCase;

final class ContactChannelsResolverTest extends TestCase
{
    private function resolver(): ContactChannelsResolver
    {
        return new ContactChannelsResolver(new ContactChannelRegistry);
    }

    public function test_legacy_phone_synthesized_when_channels_empty(): void
    {
        $r = $this->resolver();
        $p = $r->present([
            'channels' => [],
            'phone' => '+7 (913) 060-86-89',
        ]);
        $this->assertCount(1, $p->primaryChannels);
        $this->assertSame(ContactChannelType::Phone, $p->primaryChannels[0]->type);
        $this->assertStringStartsWith('tel:', $p->primaryChannels[0]->href);
    }

    public function test_channels_take_precedence_over_legacy_when_usable(): void
    {
        $r = $this->resolver();
        $p = $r->present([
            'phone' => '+79991112233',
            'channels' => [[
                'type' => 'email',
                'value' => 'a@b.ru',
                'is_enabled' => true,
                'is_primary' => true,
                'sort_order' => 0,
            ]],
        ]);
        $all = $p->allUsableChannels();
        $this->assertCount(1, $all);
        $this->assertSame(ContactChannelType::Email, $all[0]->type);
    }

    public function test_primary_and_secondary_split(): void
    {
        $r = $this->resolver();
        $p = $r->present([
            'channels' => [
                ['type' => 'phone', 'value' => '+79130000000', 'is_enabled' => true, 'is_primary' => true, 'sort_order' => 0],
                ['type' => 'email', 'value' => 'x@y.ru', 'is_enabled' => true, 'is_primary' => false, 'sort_order' => 1],
            ],
        ]);
        $this->assertCount(1, $p->primaryChannels);
        $this->assertCount(1, $p->secondaryChannels);
    }

    public function test_generic_url_open_in_new_tab_default_true(): void
    {
        $r = $this->resolver();
        $p = $r->present([
            'channels' => [[
                'type' => 'generic_url',
                'value' => 'https://example.com',
                'label' => 'Example',
                'is_enabled' => true,
                'is_primary' => false,
                'sort_order' => 0,
                'open_in_new_tab' => 'inherit',
            ]],
        ]);
        $ch = $p->secondaryChannels[0] ?? $p->primaryChannels[0];
        $this->assertTrue($ch->openInNewTab);
    }

    public function test_site_form_open_in_new_tab_default_false(): void
    {
        $r = $this->resolver();
        $p = $r->present([
            'channels' => [[
                'type' => 'site_form',
                'value' => '#lead-form',
                'is_enabled' => true,
                'is_primary' => true,
                'sort_order' => 0,
                'open_in_new_tab' => 'inherit',
            ]],
        ]);
        $this->assertFalse($p->primaryChannels[0]->openInNewTab);
    }

    public function test_override_url_requires_flag(): void
    {
        $r = $this->resolver();
        $row = [
            'type' => 'phone',
            'value' => '+79130000000',
            'url' => 'https://evil.example',
            'is_override_url' => false,
            'is_enabled' => true,
            'is_primary' => true,
            'sort_order' => 0,
        ];
        $href = $r->previewHrefForRow($row);
        $this->assertStringStartsWith('tel:', (string) $href);
    }

    public function test_analyze_counts_usable_not_just_enabled(): void
    {
        $r = $this->resolver();
        $a = $r->analyze([
            'channels' => [
                ['type' => 'phone', 'value' => '', 'is_enabled' => true, 'is_primary' => false, 'sort_order' => 0],
                ['type' => 'email', 'value' => 'ok@ok.ru', 'is_enabled' => true, 'is_primary' => false, 'sort_order' => 1],
            ],
            'address' => 'Somewhere',
        ]);
        $this->assertSame(2, $a->enabledCount);
        $this->assertSame(1, $a->usableCount);
        $this->assertSame(1, $a->brokenEnabledCount);
    }

    public function test_four_primary_channels_all_in_primary_list(): void
    {
        $r = $this->resolver();
        $p = $r->present([
            'channels' => [
                ['type' => 'phone', 'value' => '+79131111111', 'is_enabled' => true, 'is_primary' => true, 'sort_order' => 0],
                ['type' => 'phone', 'value' => '+79132222222', 'is_enabled' => true, 'is_primary' => true, 'sort_order' => 1],
                ['type' => 'phone', 'value' => '+79133333333', 'is_enabled' => true, 'is_primary' => true, 'sort_order' => 2],
                ['type' => 'phone', 'value' => '+79134444444', 'is_enabled' => true, 'is_primary' => true, 'sort_order' => 3],
            ],
        ]);
        $this->assertCount(4, $p->primaryChannels);
        $this->assertCount(0, $p->secondaryChannels);
    }

    public function test_max_href_accepts_schemeless_max_ru_and_nickname(): void
    {
        $r = $this->resolver();
        $cases = [
            ['max.ru/motolevins', 'https://max.ru/motolevins'],
            ['www.max.ru/u/abc123xy', 'https://max.ru/u/abc123xy'],
            ['u/abcd1234', 'https://max.ru/u/abcd1234'],
            ['@mybot', 'https://max.ru/@mybot'],
            ['motolevins', 'https://max.ru/motolevins'],
        ];
        foreach ($cases as [$value, $expectedPrefix]) {
            $p = $r->present([
                'channels' => [[
                    'type' => 'max',
                    'value' => $value,
                    'is_enabled' => true,
                    'is_primary' => true,
                    'sort_order' => 0,
                ]],
            ]);
            $this->assertCount(1, $p->primaryChannels, 'value: '.$value);
            $this->assertSame($expectedPrefix, $p->primaryChannels[0]->href);
        }
    }

    public function test_max_href_rejects_digits_only_to_avoid_phone_collision(): void
    {
        $r = $this->resolver();
        $p = $r->present([
            'channels' => [[
                'type' => 'max',
                'value' => '79130000000',
                'is_enabled' => true,
                'is_primary' => true,
                'sort_order' => 0,
            ]],
        ]);
        $this->assertCount(0, $p->primaryChannels);
    }

    public function test_schemeless_social_hrefs_normalize_to_https(): void
    {
        $r = $this->resolver();
        $cases = [
            ['telegram', 't.me/motolevins', 'https://t.me/motolevins'],
            ['telegram', 'telegram.me/foo_bar', 'https://telegram.me/foo_bar'],
            ['vk', 'vk.com/club1', 'https://vk.com/club1'],
            ['whatsapp', 'wa.me/79130000000', 'https://wa.me/79130000000'],
            ['instagram', 'instagram.com/foo.bar', 'https://instagram.com/foo.bar'],
            ['facebook_messenger', 'm.me/123', 'https://m.me/123'],
        ];
        foreach ($cases as [$type, $value, $expectedHref]) {
            $p = $r->present([
                'channels' => [[
                    'type' => $type,
                    'value' => $value,
                    'is_enabled' => true,
                    'is_primary' => true,
                    'sort_order' => 0,
                ]],
            ]);
            $this->assertCount(1, $p->primaryChannels, $type.' '.$value);
            $this->assertSame($expectedHref, $p->primaryChannels[0]->href, $type.' '.$value);
        }
    }

    public function test_generic_url_accepts_https_with_query_via_permissive_parser(): void
    {
        $r = $this->resolver();
        $p = $r->present([
            'channels' => [[
                'type' => 'generic_url',
                'value' => 'https://example.com/x?a=1&b=two',
                'label' => 'Link',
                'is_enabled' => true,
                'is_primary' => true,
                'sort_order' => 0,
            ]],
        ]);
        $this->assertCount(1, $p->primaryChannels);
        $this->assertSame('https://example.com/x?a=1&b=two', $p->primaryChannels[0]->href);
    }
}
