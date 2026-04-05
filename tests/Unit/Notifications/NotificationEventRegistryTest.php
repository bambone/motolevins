<?php

namespace Tests\Unit\Notifications;

use App\Models\CrmRequest;
use App\NotificationCenter\NotificationEventDefinition;
use App\NotificationCenter\NotificationEventRegistry;
use App\NotificationCenter\NotificationSeverity;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NotificationEventRegistryTest extends TestCase
{
    public function test_definition_returns_metadata_for_known_keys(): void
    {
        $def = NotificationEventRegistry::definition('crm_request.created');
        $this->assertInstanceOf(NotificationEventDefinition::class, $def);
        $this->assertSame('crm_request.created', $def->key);
        $this->assertSame(class_basename(CrmRequest::class), $def->subjectType);
        $this->assertSame(NotificationSeverity::High, $def->defaultSeverity);
    }

    public function test_unknown_key_rejected(): void
    {
        $this->assertFalse(NotificationEventRegistry::has('not.a.real.event'));
        $this->assertNull(NotificationEventRegistry::definition('not.a.real.event'));
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function knownKeysProvider(): iterable
    {
        foreach (array_keys(NotificationEventRegistry::all()) as $key) {
            yield $key => [$key];
        }
    }

    #[DataProvider('knownKeysProvider')]
    public function test_all_registered_keys_resolve(string $key): void
    {
        $this->assertTrue(NotificationEventRegistry::has($key));
        $def = NotificationEventRegistry::definition($key);
        $this->assertSame($key, $def->key);
        $this->assertNotSame('', $def->subjectType);
    }
}
