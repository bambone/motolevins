<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Reviews\Import\ReviewImportSourceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReviewImportSource extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'provider',
        'title',
        'source_url',
        'external_owner_id',
        'external_topic_id',
        'external_place_id',
        'status',
        'settings_json',
        'last_synced_at',
        'last_error_code',
        'last_error_message',
        'created_by',
    ];

    protected $casts = [
        'settings_json' => 'array',
        'last_synced_at' => 'datetime',
    ];

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(ReviewImportRun::class, 'review_import_source_id');
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(ReviewImportCandidate::class, 'review_import_source_id');
    }

    public function isPreviewSupported(): bool
    {
        return match ($this->provider) {
            'vk_topic', 'vk_wall' => true,
            'manual', 'two_gis', 'yandex_maps' => false,
            default => false,
        };
    }

    public function refreshUnsupportedStatusIfNeeded(): void
    {
        if ($this->provider === 'two_gis') {
            $this->status = ReviewImportSourceStatus::UNSUPPORTED;
        } elseif ($this->provider === 'yandex_maps') {
            $this->status = ReviewImportSourceStatus::NEEDS_AUTH;
        }
    }
}
