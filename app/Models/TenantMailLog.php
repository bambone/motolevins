<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantMailLog extends Model
{
    public const STATUS_QUEUED = 'queued';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUS_DEFERRED = 'deferred';

    protected $fillable = [
        'tenant_id',
        'correlation_id',
        'queue_job_id',
        'mailable_class',
        'mail_type',
        'mail_group',
        'to_email',
        'subject',
        'status',
        'error_message',
        'attempts',
        'throttled_count',
        'queued_at',
        'started_at',
        'sent_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'queued_at' => 'datetime',
            'started_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_QUEUED => 'В очереди',
            self::STATUS_PROCESSING => 'Отправка',
            self::STATUS_SENT => 'Отправлено',
            self::STATUS_FAILED => 'Ошибка',
            self::STATUS_DEFERRED => 'Отложено (лимит)',
        ];
    }
}
