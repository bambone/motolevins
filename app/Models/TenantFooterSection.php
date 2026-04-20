<?php

declare(strict_types=1);

namespace App\Models;

use App\Tenant\Footer\FooterSectionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenantFooterSection extends Model
{
    protected $fillable = [
        'tenant_id',
        'section_key',
        'type',
        'title',
        'body',
        'meta_json',
        'sort_order',
        'is_enabled',
        'theme_scope',
    ];

    protected function casts(): array
    {
        return [
            'meta_json' => 'array',
            'is_enabled' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function links(): HasMany
    {
        return $this->hasMany(TenantFooterLink::class, 'section_id')->orderBy('sort_order');
    }

    public static function typeLabel(string $type): string
    {
        return FooterSectionType::label($type);
    }
}
