<?php

namespace Tests\Unit\Product\CRM\Notifications;

use App\Models\CrmRequest;
use App\Product\CRM\Notifications\PlatformContactTelegramMessage;
use Tests\TestCase;

class PlatformContactTelegramMessageTest extends TestCase
{
    public function test_omits_message_section_when_message_empty(): void
    {
        $crm = new CrmRequest([
            'id' => 99,
            'request_type' => 'platform_contact',
            'name' => 'N',
            'phone' => '+1',
            'message' => '',
        ]);

        $text = PlatformContactTelegramMessage::build($crm)['text'];

        $this->assertStringNotContainsString('Сообщение:', $text);
    }

    public function test_omits_utm_block_when_all_empty(): void
    {
        $crm = new CrmRequest([
            'id' => 1,
            'request_type' => 'platform_contact',
            'name' => 'N',
            'phone' => '+1',
            'utm_source' => null,
            'utm_medium' => '',
        ]);

        $text = PlatformContactTelegramMessage::build($crm)['text'];

        $this->assertStringNotContainsString('UTM', $text);
    }

    public function test_telegram_contact_value_is_clickable_t_me_link(): void
    {
        $crm = new CrmRequest([
            'id' => 21,
            'request_type' => 'platform_contact',
            'name' => 'N',
            'phone' => '+1',
            'preferred_contact_channel' => 'telegram',
            'preferred_contact_value' => 'puhro74',
        ]);

        $content = PlatformContactTelegramMessage::build($crm);

        $this->assertSame('HTML', $content['parse_mode']);
        $this->assertStringContainsString(
            '<a href="https://t.me/puhro74">puhro74</a>',
            $content['text'],
        );
    }
}
