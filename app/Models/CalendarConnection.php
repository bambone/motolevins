<?php

declare(strict_types=1);

namespace App\Models;

use App\Scheduling\Enums\CalendarAccessMode;
use App\Scheduling\Enums\CalendarProviderType;
use App\Scheduling\Enums\SchedulingScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CalendarConnection extends Model
{
    protected $table = 'calendar_connections';

    protected $fillable = [
        'scheduling_scope',
        'tenant_id',
        'scheduling_resource_id',
        'provider',
        'access_mode',
        'account_email',
        'display_name',
        'credentials_encrypted',
        'status',
        'last_sync_at',
        'last_error',
        'last_successful_sync_at',
        'stale_after_seconds',
        'is_active',
        'owner_user_id',
    ];

    protected function casts(): array
    {
        return [
            'scheduling_scope' => SchedulingScope::class,
            'provider' => CalendarProviderType::class,
            'access_mode' => CalendarAccessMode::class,
            'last_sync_at' => 'datetime',
            'last_successful_sync_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function schedulingResource(): BelongsTo
    {
        return $this->belongsTo(SchedulingResource::class, 'scheduling_resource_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(CalendarSubscription::class, 'calendar_connection_id');
    }
}
