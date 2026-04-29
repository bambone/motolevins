<?php

declare(strict_types=1);

namespace App\Services\Reviews\Imports;

use App\Models\ReviewImportCandidate;
use App\Models\ReviewImportRun;
use App\Models\ReviewImportSource;
use App\Reviews\Import\ReviewImportCandidateStatus;
use App\Reviews\Import\ReviewImportRunStatus;
use App\Reviews\Import\ReviewImportSourceStatus;
use App\Services\Reviews\Imports\Dto\ReviewFetchOptions;
use App\Services\Reviews\Imports\Providers\TwoGisStubReviewProvider;
use App\Services\Reviews\Imports\Providers\VkCommentsReviewProvider;
use App\Services\Reviews\Imports\Providers\YandexStubReviewProvider;
use Illuminate\Support\Facades\DB;
use Throwable;

final class ReviewImportPreviewService
{
    public function __construct(
        private readonly VkCommentsReviewProvider $vk,
        private readonly TwoGisStubReviewProvider $twoGis,
        private readonly YandexStubReviewProvider $yandex,
    ) {}

    public function runPreview(ReviewImportSource $source): ReviewImportRun
    {
        $opts = new ReviewFetchOptions(
            minTextLength: (int) config('reviews.import.min_text_length', 30),
            maxPerRun: (int) config('reviews.import.max_per_run', 100),
            pageSize: (int) config('reviews.import.vk_page_size', 100),
            maxPages: (int) config('reviews.import.vk_max_pages_per_run', 5),
        );

        $run = new ReviewImportRun([
            'tenant_id' => $source->tenant_id,
            'review_import_source_id' => $source->id,
            'status' => ReviewImportRunStatus::RUNNING,
            'started_at' => now(),
        ]);
        $run->save();

        try {
            if ($source->provider === 'manual') {
                $run->status = ReviewImportRunStatus::SUCCESS;
                $run->finished_at = now();
                $run->save();

                return $run;
            }

            $provider = $this->resolveProvider($source);
            $ref = $provider->parseSourceUrl($source->source_url);
            $normalizedRef = $ref->fingerprint();

            $result = $provider->fetchPreview($source, $opts);

            $run->fetched_count = $result->fetchedCount;
            $run->error_count = $result->errorCount;

            if (! $result->ok) {
                $run->status = ReviewImportRunStatus::FAILED;
                $run->error_code = $result->errorCode;
                $run->error_message = $result->errorMessage;
                $run->finished_at = now();
                $run->save();

                $source->last_error_code = $result->errorCode;
                $source->last_error_message = $result->errorMessage;
                if (in_array((string) $result->errorCode, ['5', 'needs_token', 'access_token'], true)) {
                    $source->status = ReviewImportSourceStatus::NEEDS_AUTH;
                } elseif (in_array($source->provider, ['two_gis', 'yandex_maps'], true)) {
                    $source->status = $source->provider === 'two_gis'
                        ? ReviewImportSourceStatus::UNSUPPORTED
                        : ReviewImportSourceStatus::NEEDS_AUTH;
                } else {
                    $source->status = ReviewImportSourceStatus::FAILED;
                }
                $source->save();

                return $run;
            }

            $dup = 0;
            $created = 0;
            foreach ($result->items as $item) {
                $extId = $item->externalId ?? '';
                $hash = $extId !== ''
                    ? ReviewImportDedupe::hashExternal($source->provider, $normalizedRef, $extId)
                    : ReviewImportDedupe::hashNoExternal(
                        $source->provider,
                        $item->authorName,
                        ReviewImportDedupe::reviewedAtIso($item->reviewedAt),
                        $item->body,
                    );

                $exists = ReviewImportCandidate::query()->where('tenant_id', $source->tenant_id)
                    ->where('provider', $source->provider)
                    ->where('dedupe_hash', $hash)
                    ->exists();
                if ($exists) {
                    $dup++;
                    continue;
                }

                ReviewImportCandidate::query()->create([
                    'tenant_id' => $source->tenant_id,
                    'review_import_source_id' => $source->id,
                    'review_import_run_id' => $run->id,
                    'provider' => $source->provider,
                    'external_review_id' => $item->externalId,
                    'dedupe_hash' => $hash,
                    'author_name' => $item->authorName,
                    'author_avatar_url' => $item->authorAvatarUrl,
                    'rating' => $item->rating,
                    'reviewed_at' => $item->reviewedAt,
                    'body' => $item->body,
                    'source_url' => $item->sourceUrl,
                    'media_json' => $item->media,
                    'raw_payload_json' => $item->rawPayload,
                    'status' => ReviewImportCandidateStatus::NEW,
                ]);
                $created++;
            }

            $run->candidate_count = $created;
            $run->duplicate_count = $dup;
            $run->status = ReviewImportRunStatus::SUCCESS;
            $run->finished_at = now();
            $run->save();

            $source->status = ReviewImportSourceStatus::READY;
            $source->last_synced_at = now();
            $source->last_error_code = null;
            $source->last_error_message = null;
            $source->save();
        } catch (Throwable $e) {
            report($e);
            $run->status = ReviewImportRunStatus::FAILED;
            $run->error_code = 'exception';
            $run->error_message = 'Import failed.';
            $run->finished_at = now();
            $run->save();

            $source->status = ReviewImportSourceStatus::FAILED;
            $source->last_error_code = 'exception';
            $source->last_error_message = 'Import failed.';
            $source->save();
        }

        return $run;
    }

    private function resolveProvider(ReviewImportSource $source): Contracts\ExternalReviewProvider
    {
        return match ($source->provider) {
            'vk_topic', 'vk_wall' => $this->vk,
            'two_gis' => $this->twoGis,
            'yandex_maps' => $this->yandex,
            default => throw new \InvalidArgumentException('Unsupported import provider: '.$source->provider),
        };
    }

    /**
     * @param  list<array{author_name?: string|null, body: string, rating?: int|null, reviewed_at?: string|null, author_avatar_url?: string|null, source_url?: string|null}>  $rows
     */
    public function ingestManualRows(ReviewImportSource $source, array $rows): int
    {
        $n = 0;
        DB::transaction(function () use ($source, $rows, &$n): void {
            foreach ($rows as $row) {
                $body = trim((string) ($row['body'] ?? ''));
                if ($body === '') {
                    continue;
                }
                $author = isset($row['author_name']) ? trim((string) $row['author_name']) : null;
                $rating = isset($row['rating']) ? (int) $row['rating'] : null;
                if ($rating !== null && ($rating < 1 || $rating > 5)) {
                    $rating = null;
                }
                $hash = ReviewImportDedupe::hashNoExternal(
                    'manual',
                    $author ?: '',
                    isset($row['reviewed_at']) ? trim((string) $row['reviewed_at']) : '',
                    $body,
                );
                if (ReviewImportCandidate::query()->where('tenant_id', $source->tenant_id)
                    ->where('provider', 'manual')
                    ->where('dedupe_hash', $hash)
                    ->exists()) {
                    continue;
                }
                ReviewImportCandidate::query()->create([
                    'tenant_id' => $source->tenant_id,
                    'review_import_source_id' => $source->id,
                    'review_import_run_id' => null,
                    'provider' => 'manual',
                    'external_review_id' => null,
                    'dedupe_hash' => $hash,
                    'author_name' => $author,
                    'author_avatar_url' => isset($row['author_avatar_url']) ? trim((string) $row['author_avatar_url']) : null,
                    'rating' => $rating,
                    'reviewed_at' => isset($row['reviewed_at']) && $row['reviewed_at'] !== ''
                        ? \Carbon\Carbon::parse((string) $row['reviewed_at'])
                        : null,
                    'body' => $body,
                    'source_url' => isset($row['source_url']) ? trim((string) $row['source_url']) : null,
                    'raw_payload_json' => $row,
                    'status' => ReviewImportCandidateStatus::NEW,
                ]);
                $n++;
            }
        });

        return $n;
    }
}
