<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantSeoFileGeneration extends Model
{
    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SKIPPED = 'skipped';

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_DEPLOY_HOOK = 'deploy_hook';

    public const SOURCE_SCHEDULE = 'schedule';

    public const SOURCE_SYSTEM = 'system';

    protected $fillable = [
        'tenant_id',
        'type',
        'status',
        'trigger_source',
        'triggered_by_user_id',
        'overwrite_confirmed',
        'backup_created',
        'backup_storage_path',
        'started_at',
        'finished_at',
        'error_message',
        'meta_json',
    ];

    protected $casts = [
        'overwrite_confirmed' => 'boolean',
        'backup_created' => 'boolean',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'meta_json' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by_user_id');
    }
}
