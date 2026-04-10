<?php

declare(strict_types=1);

namespace App\Models;

use App\Scheduling\Enums\AppointmentHoldStatus;
use App\Scheduling\Enums\SchedulingScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentHold extends Model
{
    protected $table = 'appointment_holds';

    protected $fillable = [
        'scheduling_scope',
        'tenant_id',
        'bookable_service_id',
        'scheduling_resource_id',
        'crm_request_id',
        'starts_at_utc',
        'ends_at_utc',
        'status',
        'source',
        'expires_at',
        'client_name',
        'client_email',
        'client_phone',
        'payload_json',
    ];

    protected function casts(): array
    {
        return [
            'scheduling_scope' => SchedulingScope::class,
            'starts_at_utc' => 'datetime',
            'ends_at_utc' => 'datetime',
            'expires_at' => 'datetime',
            'status' => AppointmentHoldStatus::class,
            'payload_json' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function bookableService(): BelongsTo
    {
        return $this->belongsTo(BookableService::class, 'bookable_service_id');
    }

    public function schedulingResource(): BelongsTo
    {
        return $this->belongsTo(SchedulingResource::class, 'scheduling_resource_id');
    }

    public function crmRequest(): BelongsTo
    {
        return $this->belongsTo(CrmRequest::class, 'crm_request_id');
    }
}
