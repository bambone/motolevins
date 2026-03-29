<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmRequestActivity extends Model
{
    public const TYPE_INBOUND_RECEIVED = 'inbound_received';

    public const TYPE_STATUS_CHANGED = 'status_changed';

    public const TYPE_NOTE_ADDED = 'note_added';

    public const TYPE_MAIL_QUEUED = 'mail_queued';

    public $timestamps = false;

    protected $fillable = [
        'crm_request_id',
        'type',
        'meta',
        'actor_user_id',
        'created_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->created_at = $model->created_at ?? now();
        });
    }

    public function crmRequest(): BelongsTo
    {
        return $this->belongsTo(CrmRequest::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            self::TYPE_INBOUND_RECEIVED => 'Входящая заявка',
            self::TYPE_STATUS_CHANGED => 'Смена статуса',
            self::TYPE_NOTE_ADDED => 'Заметка',
            self::TYPE_MAIL_QUEUED => 'Письмо в очереди',
            default => $type,
        };
    }
}
