<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalBusyBlock extends Model
{
    protected $table = 'external_busy_blocks';

    protected $fillable = [
        'scheduling_resource_id',
        'scheduling_target_id',
        'calendar_subscription_id',
        'starts_at_utc',
        'ends_at_utc',
        'source_event_id',
        'is_tentative',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'starts_at_utc' => 'datetime',
            'ends_at_utc' => 'datetime',
            'is_tentative' => 'boolean',
            'raw_payload' => 'array',
        ];
    }

    public function schedulingResource(): BelongsTo
    {
        return $this->belongsTo(SchedulingResource::class, 'scheduling_resource_id');
    }

    public function schedulingTarget(): BelongsTo
    {
        return $this->belongsTo(SchedulingTarget::class, 'scheduling_target_id');
    }

    public function calendarSubscription(): BelongsTo
    {
        return $this->belongsTo(CalendarSubscription::class, 'calendar_subscription_id');
    }
}
