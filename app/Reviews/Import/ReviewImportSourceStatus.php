<?php

declare(strict_types=1);

namespace App\Reviews\Import;

final class ReviewImportSourceStatus
{
    public const DRAFT = 'draft';

    public const READY = 'ready';

    public const NEEDS_AUTH = 'needs_auth';

    public const UNSUPPORTED = 'unsupported';

    public const FAILED = 'failed';

    public const DISABLED = 'disabled';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::DRAFT,
            self::READY,
            self::NEEDS_AUTH,
            self::UNSUPPORTED,
            self::FAILED,
            self::DISABLED,
        ];
    }
}
