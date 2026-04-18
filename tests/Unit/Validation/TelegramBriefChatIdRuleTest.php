<?php

declare(strict_types=1);

namespace Tests\Unit\Validation;

use App\Validation\TelegramBriefChatIdRule;
use PHPUnit\Framework\TestCase;

class TelegramBriefChatIdRuleTest extends TestCase
{
    public function test_accepts_empty(): void
    {
        $this->assertValidated('', true);
    }

    public function test_accepts_numeric_and_negative_ids(): void
    {
        $this->assertValidated('123456789', true);
        $this->assertValidated('-1001234567890', true);
    }

    public function test_accepts_username(): void
    {
        $this->assertValidated('@channel_name', true);
    }

    public function test_rejects_garbage(): void
    {
        $this->assertValidated('not an id', false);
    }

    private function assertValidated(string $value, bool $expectOk): void
    {
        $rule = new TelegramBriefChatIdRule();
        $failed = false;
        $rule->validate('dest_telegram_chat_id', $value, function () use (&$failed): void {
            $failed = true;
        });
        $this->assertSame($expectOk, ! $failed);
    }
}
