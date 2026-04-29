<?php

declare(strict_types=1);

namespace App\Services\Reviews\Imports;

use App\Models\Review;
use App\Models\ReviewImportCandidate;
use App\Reviews\Import\ReviewImportCandidateStatus;
use Illuminate\Support\Facades\DB;

final class ReviewCandidateImportService
{
    public function __construct(
        private readonly ReviewAvatarImportService $avatars,
    ) {}

    /**
     * @param  iterable<ReviewImportCandidate>  $candidates
     * @return list<int> imported review ids
     */
    public function importCandidates(iterable $candidates, bool $publishImmediately, ?int $forcedRating = null): array
    {
        $ids = [];
        foreach ($candidates as $candidate) {
            if ($candidate->status === ReviewImportCandidateStatus::IMPORTED) {
                continue;
            }
            $reviewId = DB::transaction(function () use ($candidate, $publishImmediately, $forcedRating): int {
                $pathAvatar = null;
                if (config('reviews.import.download_avatars', true) && filled($candidate->author_avatar_url)) {
                    $pathAvatar = $this->avatars->downloadToTenantPublic(
                        (string) $candidate->author_avatar_url,
                        (int) $candidate->tenant_id,
                        (string) $candidate->provider,
                    );
                }

                $rating = $forcedRating ?? $candidate->rating;

                $review = new Review;
                $review->tenant_id = $candidate->tenant_id;
                $review->name = $candidate->author_name ?: 'Гость';
                $review->city = null;
                $review->body = $candidate->body;
                $review->rating = $rating;
                $review->status = $publishImmediately ? 'published' : 'draft';
                $review->is_featured = false;
                $review->sort_order = 5000;
                $review->source = 'import';
                $review->source_provider = $candidate->provider;
                $review->source_external_id = $candidate->external_review_id;
                $review->source_url = $candidate->source_url;
                $review->review_import_source_id = $candidate->review_import_source_id;
                $review->source_original_body = $candidate->body;
                $review->source_payload_json = $candidate->raw_payload_json;
                $review->imported_at = now();
                $review->date = $candidate->reviewed_at?->toDateString();
                $review->media_type = 'text';
                if ($pathAvatar) {
                    $review->avatar = $pathAvatar;
                } elseif (filled($candidate->author_avatar_url)) {
                    $review->meta_json = array_merge($review->meta_json ?? [], [
                        'avatar_external_url' => $candidate->author_avatar_url,
                    ]);
                }
                $review->save();

                $candidate->imported_review_id = $review->id;
                $candidate->status = ReviewImportCandidateStatus::IMPORTED;
                $candidate->save();

                $review->review_import_candidate_id = $candidate->id;
                $review->save();

                return $review->id;
            });
            $ids[] = $reviewId;
        }

        return $ids;
    }
}
