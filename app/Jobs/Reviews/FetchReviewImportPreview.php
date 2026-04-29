<?php

declare(strict_types=1);

namespace App\Jobs\Reviews;

use App\Models\ReviewImportSource;
use App\Services\Reviews\Imports\ReviewImportPreviewService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class FetchReviewImportPreview implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout;

    public function __construct(
        public int $sourceId,
    ) {
        $this->timeout = (int) config('reviews.import.timeout', 60);
        $this->onQueue((string) config('reviews.import.queue', 'default'));
    }

    public function handle(ReviewImportPreviewService $preview): void
    {
        $source = ReviewImportSource::query()->withoutGlobalScopes()->find($this->sourceId);
        if ($source === null) {
            return;
        }

        $preview->runPreview($source);
    }
}
