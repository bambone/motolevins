<?php

namespace App\NotificationCenter;

use App\Models\NotificationEvent;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * dedupe_key = null → dedupe disabled (always allow new event).
 * dedupe_key set → application guard + UNIQUE(tenant_id, event_key, dedupe_key).
 */
final class NotificationDedupeService
{
    public function shouldAttemptInsert(?string $dedupeKey): bool
    {
        return true;
    }

    /**
     * @return array{event: NotificationEvent|null, duplicate: bool}
     */
    public function tryCreateEvent(array $attributes): array
    {
        if (array_key_exists('dedupe_key', $attributes)) {
            $raw = $attributes['dedupe_key'];
            if ($raw === '' || (is_string($raw) && trim($raw) === '')) {
                $attributes['dedupe_key'] = null;
            }
        }

        $dedupeKey = $attributes['dedupe_key'] ?? null;
        if ($dedupeKey === null) {
            $event = NotificationEvent::query()->create($attributes);

            return ['event' => $event, 'duplicate' => false];
        }

        $tenantId = (int) $attributes['tenant_id'];
        $eventKey = (string) $attributes['event_key'];

        $query = NotificationEvent::query()
            ->where('tenant_id', $tenantId)
            ->where('event_key', $eventKey)
            ->where('dedupe_key', $dedupeKey);

        if (DB::transactionLevel() > 0) {
            $query->lockForUpdate();
        }

        $existing = $query->first();

        if ($existing !== null) {
            return ['event' => null, 'duplicate' => true];
        }

        try {
            $event = NotificationEvent::query()->create($attributes);

            return ['event' => $event, 'duplicate' => false];
        } catch (QueryException $e) {
            if ($this->isDuplicateKeyException($e)) {
                return ['event' => null, 'duplicate' => true];
            }

            throw $e;
        }
    }

    private function isDuplicateKeyException(QueryException $e): bool
    {
        $code = (string) $e->getCode();
        $msg = strtolower($e->getMessage());

        // MySQL 1062, SQLite UNIQUE constraint
        return $code === '23000'
            || str_contains($msg, 'duplicate')
            || str_contains($msg, 'unique constraint');
    }
}
