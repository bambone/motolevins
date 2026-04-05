<?php

namespace Tests\Unit\Notifications;

use App\NotificationCenter\NotificationPayloadDto;
use InvalidArgumentException;
use Tests\TestCase;

class NotificationPayloadDtoTest extends TestCase
{
    public function test_serializes_expected_keys(): void
    {
        $dto = new NotificationPayloadDto('T', 'B', 'https://x.test/a', 'Open', ['x' => 1]);
        $this->assertSame([
            'title' => 'T',
            'body' => 'B',
            'action_url' => 'https://x.test/a',
            'action_label' => 'Open',
            'meta' => ['x' => 1],
        ], $dto->toArray());
    }

    public function test_from_stored_array_round_trip(): void
    {
        $original = new NotificationPayloadDto('A', 'B', null, 'L', ['k' => 'v']);
        $restored = NotificationPayloadDto::fromStoredArray($original->toArray());
        $this->assertEquals($original, $restored);
    }

    public function test_from_validated_array_rejects_empty_title_or_body(): void
    {
        $this->expectException(InvalidArgumentException::class);
        NotificationPayloadDto::fromValidatedArray(['title' => ' ', 'body' => 'x']);
    }

    public function test_assert_valid_for_recording_rejects_whitespace_only(): void
    {
        $dto = new NotificationPayloadDto(' ', 'body', null, null, []);
        $this->expectException(InvalidArgumentException::class);
        $dto->assertValidForRecording();
    }

    public function test_meta_defaults_when_missing_in_stored_array(): void
    {
        $dto = NotificationPayloadDto::fromStoredArray(['title' => 'a', 'body' => 'b']);
        $this->assertSame([], $dto->meta);
        $this->assertNull($dto->actionUrl);
    }
}
