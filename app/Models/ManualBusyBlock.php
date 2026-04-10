<?php

declare(strict_types=1);

namespace App\Models;

use App\Scheduling\Enums\ManualBusySeverity;
use App\Scheduling\Enums\ManualBusySource;
use App\Scheduling\Enums\SchedulingScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualBusyBlock extends Model
{
    protected $table = 'manual_busy_blocks';

    protected $fillable = [
        'scheduling_scope',
        'tenant_id',
        'scheduling_target_id',
        'scheduling_resource_id',
        'starts_at_utc',
        'ends_at_utc',
        'reason',
        'created_by',
        'severity',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'scheduling_scope' => SchedulingScope::class,
            'starts_at_utc' => 'datetime',
            'ends_at_utc' => 'datetime',
            'severity' => ManualBusySeverity::class,
            'source' => ManualBusySource::class,
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function schedulingTarget(): BelongsTo
    {
        return $this->belongsTo(SchedulingTarget::class, 'scheduling_target_id');
    }

    public function schedulingResource(): BelongsTo
    {
        return $this->belongsTo(SchedulingResource::class, 'scheduling_resource_id');
    }
}
