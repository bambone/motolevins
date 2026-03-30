<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantSeoFile extends Model
{
    public const TYPE_ROBOTS_TXT = 'robots_txt';

    public const TYPE_SITEMAP_XML = 'sitemap_xml';

    protected $fillable = [
        'tenant_id',
        'type',
        'storage_disk',
        'storage_path',
        'public_url',
        'exists',
        'checksum',
        'size_bytes',
        'generated_at',
        'last_checked_at',
        'last_public_content_change_at',
        'freshness_status',
        'last_generated_by_user_id',
        'last_generation_source',
        'backup_storage_path',
        'meta_json',
    ];

    protected $casts = [
        'exists' => 'boolean',
        'generated_at' => 'datetime',
        'last_checked_at' => 'datetime',
        'last_public_content_change_at' => 'datetime',
        'meta_json' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function lastGeneratedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_generated_by_user_id');
    }
}
