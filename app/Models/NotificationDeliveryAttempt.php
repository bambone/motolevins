<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationDeliveryAttempt extends Model
{
    protected $table = 'notification_delivery_attempts';

    protected $fillable = [
        'delivery_id',
        'attempt_no',
        'status',
        'started_at',
        'finished_at',
        'error_message',
        'response_json',
    ];

    protected function casts(): array
    {
        return [
            'response_json' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(NotificationDelivery::class, 'delivery_id');
    }
}
