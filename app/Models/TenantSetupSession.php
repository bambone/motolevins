<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantSetupSession extends Model
{
    protected $table = 'tenant_setup_sessions';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'session_status',
        'current_item_key',
        'current_route_name',
        'journey_version',
        'step_index',
        'visible_step_keys_json',
        'meta_json',
        'started_at',
        'paused_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'visible_step_keys_json' => 'array',
            'meta_json' => 'array',
            'started_at' => 'datetime',
            'paused_at' => 'datetime',
            'completed_at' => 'datetime',
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
}
