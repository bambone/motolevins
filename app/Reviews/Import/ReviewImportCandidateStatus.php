<?php

declare(strict_types=1);

namespace App\Reviews\Import;

final class ReviewImportCandidateStatus
{
    public const NEW = 'new';

    public const SELECTED = 'selected';

    public const IMPORTED = 'imported';

    public const IGNORED = 'ignored';

    public const DUPLICATE = 'duplicate';

    public const FAILED = 'failed';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::NEW,
            self::SELECTED,
            self::IMPORTED,
            self::IGNORED,
            self::DUPLICATE,
            self::FAILED,
        ];
    }
}
