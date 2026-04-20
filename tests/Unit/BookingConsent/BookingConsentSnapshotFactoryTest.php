<?php

declare(strict_types=1);

namespace Tests\Unit\BookingConsent;

use App\BookingConsent\BookingConsentSnapshotFactory;
use PHPUnit\Framework\TestCase;

class BookingConsentSnapshotFactoryTest extends TestCase
{
    public function test_is_new_schema_detects_versioned_payload(): void
    {
        $this->assertTrue(BookingConsentSnapshotFactory::isNewSchema([
            'schema_version' => 1,
            'items' => [],
        ]));
        $this->assertFalse(BookingConsentSnapshotFactory::isNewSchema([
            'accepted_at' => '2020-01-01',
        ]));
        $this->assertFalse(BookingConsentSnapshotFactory::isNewSchema(null));
    }
}
