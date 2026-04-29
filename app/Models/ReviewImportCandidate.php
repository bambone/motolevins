<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewImportCandidate extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'review_import_source_id',
        'review_import_run_id',
        'provider',
        'external_review_id',
        'dedupe_hash',
        'author_name',
        'author_avatar_url',
        'rating',
        'reviewed_at',
        'body',
        'source_url',
        'media_json',
        'raw_payload_json',
        'status',
        'imported_review_id',
        'selected_at',
        'selected_by',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'selected_at' => 'datetime',
        'media_json' => 'array',
        'raw_payload_json' => 'array',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(ReviewImportSource::class, 'review_import_source_id');
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(ReviewImportRun::class, 'review_import_run_id');
    }

    public function importedReview(): BelongsTo
    {
        return $this->belongsTo(Review::class, 'imported_review_id');
    }

    public function selectedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'selected_by');
    }
}
