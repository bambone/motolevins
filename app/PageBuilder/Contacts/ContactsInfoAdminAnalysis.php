<?php

namespace App\PageBuilder\Contacts;

/**
 * Builder / summary metrics: enabled vs usable.
 *
 * @phpstan-type RowIssues array<int, list<string>>
 */
final readonly class ContactsInfoAdminAnalysis
{
    /**
     * @param  list<string>  $warnings
     * @param  array<int, list<string>>  $rowIssues
     */
    public function __construct(
        public int $enabledCount,
        public int $usableCount,
        public int $brokenEnabledCount,
        public array $warnings,
        public array $rowIssues,
        public int $usablePrimaryCount,
        public bool $hasAddress,
        public bool $hasHours,
        public bool $hasMap,
    ) {}
}
