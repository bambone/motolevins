<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmRequestNote extends Model
{
    protected $fillable = [
        'crm_request_id',
        'user_id',
        'body',
    ];

    public function crmRequest(): BelongsTo
    {
        return $this->belongsTo(CrmRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
