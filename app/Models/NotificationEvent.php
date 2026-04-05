<?php

namespace App\Models;

use App\NotificationCenter\NotificationPayloadDto;
use Database\Factories\NotificationEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationEvent extends Model
{
    /** @use HasFactory<NotificationEventFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $table = 'notification_events';

    protected $fillable = [
        'tenant_id',
        'event_key',
        'subject_type',
        'subject_id',
        'severity',
        'dedupe_key',
        'payload_json',
        'actor_user_id',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'payload_json' => 'array',
            'occurred_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function setAttribute($key, $value): mixed
    {
        if ($key === 'payload_json' && $this->exists) {
            throw new \RuntimeException('notification_events.payload_json is immutable after insert.');
        }

        return parent::setAttribute($key, $value);
    }

    protected static function booted(): void
    {
        static::updating(function (NotificationEvent $event): void {
            if ($event->isDirty('payload_json')) {
                throw new \RuntimeException('notification_events.payload_json is immutable after insert.');
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class, 'event_id');
    }

    public function payloadDto(): NotificationPayloadDto
    {
        return NotificationPayloadDto::fromStoredArray($this->payload_json ?? []);
    }
}
