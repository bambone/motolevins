<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Transitional projection: история смен статуса Lead для совместимости.
 * Источник истины по операторскому inbound-timeline — {@see CrmRequestActivity} у {@see CrmRequest} (ADR-007).
 */
class LeadStatusHistory extends Model
{
    protected $table = 'lead_status_history';

    public $timestamps = false;

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->created_at = $model->created_at ?? now();
        });
    }

    protected $fillable = [
        'lead_id',
        'old_status',
        'new_status',
        'changed_by',
        'comment',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
