<?php

namespace App\NotificationCenter;

/**
 * Optional recipient user ids for matching personal subscriptions (null = shared only).
 */
final readonly class NotificationRoutingContext
{
    /**
     * @param  list<int>|null  $recipientUserIds
     */
    public function __construct(
        public ?array $recipientUserIds = null,
    ) {}

    /**
     * @param  list<int>  $ids
     */
    public static function forUsers(array $ids): self
    {
        return new self(recipientUserIds: array_values(array_unique(array_map('intval', $ids))));
    }
}
