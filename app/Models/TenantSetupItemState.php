<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantSetupItemState extends Model
{
    protected $table = 'tenant_setup_item_states';

    protected $fillable = [
        'tenant_id',
        'item_key',
        'category_key',
        'current_status',
        'applicability_status',
        'snooze_reason_code',
        'not_needed_reason_code',
        'reason_comment',
        'snooze_until',
        'completed_at',
        'completed_value_json',
        'completion_source',
        'updated_by_user_id',
        'last_seen_at',
        'last_evaluated_at',
        'last_completion_check_at',
        'last_completion_result_json',
        'last_target_route_name',
    ];

    protected function casts(): array
    {
        return [
            'snooze_until' => 'datetime',
            'completed_at' => 'datetime',
            'completed_value_json' => 'array',
            'last_seen_at' => 'datetime',
            'last_evaluated_at' => 'datetime',
            'last_completion_check_at' => 'datetime',
            'last_completion_result_json' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
