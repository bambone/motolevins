<?php

declare(strict_types=1);

namespace App\Support\Motorcycle;

use Illuminate\Support\Facades\Log;

final class MotorcycleBlockSaveLogger
{
    public static function enabled(): bool
    {
        return (bool) config('motorcycle.block_save_debug', false);
    }

    /**
     * @param  list<string>  $keys
     */
    public static function log(
        string $phase,
        string $block,
        int $recordId,
        bool $success,
        array $keys,
        float $durationMs,
        ?string $failureReason = null,
    ): void {
        if (! self::enabled()) {
            return;
        }

        $keys = array_values($keys);

        Log::debug('[motorcycle.block_save] '.$phase, [
            'record_id' => $recordId,
            'block' => $block,
            'success' => $success,
            'duration_ms' => round($durationMs, 3),
            'keys' => $keys,
            'keys_count' => count($keys),
            'failure_reason' => $failureReason,
        ]);
    }
}
