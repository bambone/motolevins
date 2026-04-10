<?php

declare(strict_types=1);

namespace App\Models;

use App\Scheduling\Enums\AvailabilityExceptionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvailabilityException extends Model
{
    protected $table = 'availability_exceptions';

    protected $fillable = [
        'scheduling_resource_id',
        'scheduling_target_id',
        'bookable_service_id',
        'exception_type',
        'starts_at_utc',
        'ends_at_utc',
        'reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'exception_type' => AvailabilityExceptionType::class,
            'starts_at_utc' => 'datetime',
            'ends_at_utc' => 'datetime',
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

    public function bookableService(): BelongsTo
    {
        return $this->belongsTo(BookableService::class, 'bookable_service_id');
    }
}
