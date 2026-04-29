<?php

declare(strict_types=1);

namespace App\Jobs\Reviews;

use App\Models\ReviewImportCandidate;
use App\Services\Reviews\Imports\ReviewCandidateImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ImportSelectedReviewCandidates implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout;

    /**
     * @param  list<int>  $candidateIds
     */
    public function __construct(
        public array $candidateIds,
        public bool $publishImmediately = false,
        public ?int $forcedRating = null,
    ) {
        $this->timeout = (int) config('reviews.import.timeout', 60);
        $this->onQueue((string) config('reviews.import.queue', 'default'));
    }

    public function handle(ReviewCandidateImportService $importer): void
    {
        $candidates = ReviewImportCandidate::query()
            ->withoutGlobalScopes()
            ->whereIn('id', $this->candidateIds)
            ->orderBy('id')
            ->get();

        $importer->importCandidates($candidates, $this->publishImmediately, $this->forcedRating);
    }
}
