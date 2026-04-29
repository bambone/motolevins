<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReviewImportRun extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'review_import_source_id',
        'status',
        'fetched_count',
        'candidate_count',
        'duplicate_count',
        'error_count',
        'error_code',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(ReviewImportSource::class, 'review_import_source_id');
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(ReviewImportCandidate::class, 'review_import_run_id');
    }
}
