<?php

namespace App\Models;

use Database\Factories\NotificationDeliveryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationDelivery extends Model
{
    /** @use HasFactory<NotificationDeliveryFactory> */
    use HasFactory;

    protected $table = 'notification_deliveries';

    protected $fillable = [
        'tenant_id',
        'event_id',
        'subscription_id',
        'destination_id',
        'channel_type',
        'status',
        'read_at',
        'queued_at',
        'sent_at',
        'delivered_at',
        'failed_at',
        'error_message',
        'provider_message_id',
        'response_json',
    ];

    protected function casts(): array
    {
        return [
            'response_json' => 'array',
            'read_at' => 'datetime',
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(NotificationEvent::class, 'event_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(NotificationSubscription::class, 'subscription_id');
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(NotificationDestination::class, 'destination_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(NotificationDeliveryAttempt::class, 'delivery_id')->orderBy('attempt_no');
    }
}
