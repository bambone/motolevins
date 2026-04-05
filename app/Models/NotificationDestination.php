<?php

namespace App\Models;

use Database\Factories\NotificationDestinationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationDestination extends Model
{
    /** @use HasFactory<NotificationDestinationFactory> */
    use HasFactory;

    protected $table = 'notification_destinations';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'name',
        'type',
        'status',
        'is_shared',
        'config_json',
        'verified_at',
        'disabled_at',
        'last_used_at',
        'last_error_at',
        'last_error_message',
    ];

    protected function casts(): array
    {
        return [
            'is_shared' => 'boolean',
            'config_json' => 'array',
            'verified_at' => 'datetime',
            'disabled_at' => 'datetime',
            'last_used_at' => 'datetime',
            'last_error_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (NotificationDestination $destination): void {
            if ($destination->user_id !== null) {
                $destination->is_shared = false;
            } else {
                $destination->is_shared = true;
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

    public function subscriptions(): BelongsToMany
    {
        return $this->belongsToMany(
            NotificationSubscription::class,
            'notification_subscription_destinations',
            'destination_id',
            'subscription_id'
        )->withPivot(['delivery_mode', 'delay_seconds', 'order_index', 'is_enabled'])
            ->withTimestamps();
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class, 'destination_id');
    }
}
