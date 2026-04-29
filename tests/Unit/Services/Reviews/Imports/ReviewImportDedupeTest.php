<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Reviews\Imports;

use App\Services\Reviews\Imports\ReviewImportDedupe;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ReviewImportDedupeTest extends TestCase
{
    public function test_hash_external_is_stable(): void
    {
        $a = ReviewImportDedupe::hashExternal('vk_topic', 'https://vk.com/foo', '99');
        $b = ReviewImportDedupe::hashExternal('vk_topic', 'https://vk.com/foo', '99');
        $this->assertSame($a, $b);
        $this->assertSame(64, strlen($a));
    }

    public function test_hash_external_changes_when_external_id_changes(): void
    {
        $a = ReviewImportDedupe::hashExternal('vk_topic', 'https://vk.com/foo', '1');
        $b = ReviewImportDedupe::hashExternal('vk_topic', 'https://vk.com/foo', '2');
        $this->assertNotSame($a, $b);
    }

    public function test_hash_no_external_normalizes_whitespace_and_case_for_body_only_in_hash(): void
    {
        $a = ReviewImportDedupe::hashNoExternal('manual', 'Иван', '2024-01-01T00:00:00+00:00', "Спасибо\nПока");
        $b = ReviewImportDedupe::hashNoExternal('manual', 'Иван', '2024-01-01T00:00:00+00:00', 'спасибо пока');
        $this->assertSame($a, $b);
    }

    public function test_hash_no_external_differs_when_author_differs_for_same_short_body(): void
    {
        $body = 'Спасибо';
        $d = ReviewImportDedupe::reviewedAtIso(new DateTimeImmutable('2024-06-01T12:00:00+00:00'));
        $a = ReviewImportDedupe::hashNoExternal('manual', 'Аня', $d, $body);
        $b = ReviewImportDedupe::hashNoExternal('manual', 'Борис', $d, $body);
        $this->assertNotSame($a, $b);
    }
}
