<?php

declare(strict_types=1);

namespace App\Reviews\Import;

final class ReviewImportRunStatus
{
    public const QUEUED = 'queued';

    public const RUNNING = 'running';

    public const SUCCESS = 'success';

    public const PARTIAL = 'partial';

    public const FAILED = 'failed';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::QUEUED,
            self::RUNNING,
            self::SUCCESS,
            self::PARTIAL,
            self::FAILED,
        ];
    }
}
