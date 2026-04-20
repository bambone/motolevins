<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantBookingConsentItem extends Model
{
    protected $fillable = [
        'tenant_id',
        'code',
        'label',
        'link_text',
        'link_url',
        'is_required',
        'is_enabled',
        'sort_order',
        'meta_json',
    ];

    protected function casts(): array
    {
        return [
            'meta_json' => 'array',
            'is_required' => 'boolean',
            'is_enabled' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
