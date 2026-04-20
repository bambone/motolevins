<?php

declare(strict_types=1);

namespace App\Models;

use App\Tenant\Footer\TenantFooterLinkKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantFooterLink extends Model
{
    protected $fillable = [
        'section_id',
        'group_key',
        'label',
        'url',
        'link_kind',
        'target',
        'icon_key',
        'sort_order',
        'is_enabled',
        'meta_json',
    ];

    protected function casts(): array
    {
        return [
            'meta_json' => 'array',
            'is_enabled' => 'boolean',
            'sort_order' => 'integer',
            'link_kind' => TenantFooterLinkKind::class,
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(TenantFooterSection::class, 'section_id');
    }
}
