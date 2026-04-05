<?php

namespace App\NotificationCenter;

use App\Models\NotificationEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class NotificationEventRecorder
{
    public function __construct(
        private readonly NotificationDedupeService $dedupe,
        private readonly NotificationRouter $router,
    ) {}

    /**
     * Creates event + deliveries in one transaction. From domain code that already uses
     * a DB transaction, wrap the call in {@see DB::afterCommit()} so notifications run only after commit.
     *
     * @param  array<string, mixed>  $meta  (unused — use payload DTO from caller)
     * @param  list<int>|null  $recipientUserIds
     * @return array{event: NotificationEvent|null, duplicate: bool, delivery_ids: list<int>}
     */
    public function record(
        int $tenantId,
        string $eventKey,
        string $subjectType,
        int $subjectId,
        NotificationPayloadDto $payload,
        ?string $dedupeKey = null,
        ?int $actorUserId = null,
        ?NotificationSeverity $severityOverride = null,
        ?NotificationRoutingContext $routingContext = null,
    ): array {
        if (! NotificationEventRegistry::has($eventKey)) {
            throw new InvalidArgumentException('Unknown notification event_key: '.$eventKey);
        }

        $payload->assertValidForRecording();

        $definition = NotificationEventRegistry::definition($eventKey);
        if ($definition === null) {
            throw new InvalidArgumentException('Unknown notification event_key: '.$eventKey);
        }

        if ($definition->subjectType !== $subjectType) {
            throw new InvalidArgumentException(
                'notification subject_type mismatch for '.$eventKey.': expected '.$definition->subjectType.', got '.$subjectType.'.'
            );
        }

        $severity = $severityOverride ?? $definition->defaultSeverity;

        if ($dedupeKey !== null && trim($dedupeKey) === '') {
            $dedupeKey = null;
        }

        $attributes = [
            'tenant_id' => $tenantId,
            'event_key' => $eventKey,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'severity' => $severity->value,
            'dedupe_key' => $dedupeKey,
            'payload_json' => $payload->toArray(),
            'actor_user_id' => $actorUserId,
            'occurred_at' => Carbon::now(),
        ];

        return DB::transaction(function () use ($attributes, $routingContext): array {
            $result = $this->dedupe->tryCreateEvent($attributes);
            if ($result['duplicate'] || $result['event'] === null) {
                return [
                    'event' => null,
                    'duplicate' => true,
                    'delivery_ids' => [],
                ];
            }

            $event = $result['event'];
            $deliveryIds = $this->router->routeEvent($event, $routingContext);

            return [
                'event' => $event,
                'duplicate' => false,
                'delivery_ids' => $deliveryIds,
            ];
        });
    }
}
