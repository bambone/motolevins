<?php

namespace App\Models;

use Database\Factories\NotificationSubscriptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class NotificationSubscription extends Model
{
    /** @use HasFactory<NotificationSubscriptionFactory> */
    use HasFactory;

    protected $table = 'notification_subscriptions';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'name',
        'event_key',
        'enabled',
        'conditions_json',
        'schedule_json',
        'severity_min',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'conditions_json' => 'array',
            'schedule_json' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function destinations(): BelongsToMany
    {
        return $this->belongsToMany(
            NotificationDestination::class,
            'notification_subscription_destinations',
            'subscription_id',
            'destination_id'
        )->withPivot(['delivery_mode', 'delay_seconds', 'order_index', 'is_enabled'])
            ->withTimestamps()
            ->orderByPivot('order_index');
    }
}
