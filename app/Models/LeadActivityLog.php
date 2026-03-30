<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadActivityLog extends Model
{
    protected $fillable = [
        'lead_id',
        'actor_id',
        'type',
        'comment',
        'payload',
    ];

    protected $casts = [
        'payload' => 'json',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
