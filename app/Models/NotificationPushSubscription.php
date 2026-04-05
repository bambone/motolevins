<?php

namespace App\Models;

use Database\Factories\NotificationPushSubscriptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPushSubscription extends Model
{
    /** @use HasFactory<NotificationPushSubscriptionFactory> */
    use HasFactory;

    protected $table = 'notification_push_subscriptions';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'destination_id',
        'endpoint',
        'endpoint_hash',
        'public_key',
        'auth_token',
        'user_agent',
        'device_label',
        'last_seen_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (NotificationPushSubscription $subscription): void {
            $endpoint = (string) $subscription->endpoint;
            if ($endpoint !== '') {
                $subscription->endpoint_hash = hash('sha256', $endpoint);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(NotificationDestination::class, 'destination_id');
    }
}
