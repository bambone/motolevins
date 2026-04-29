<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\ReviewImportCandidate;
use App\Models\ReviewImportSource;
use App\Reviews\Import\ReviewImportCandidateStatus;
use App\Reviews\Import\ReviewImportSourceStatus;
use App\Services\Reviews\Imports\ReviewCandidateImportService;
use App\Services\Reviews\Imports\ReviewImportDedupe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

final class ReviewCandidateImportServiceTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    public function test_import_creates_draft_review_and_marks_candidate_imported(): void
    {
        Config::set('reviews.import.download_avatars', false);

        $tenant = $this->createTenantWithActiveDomain('rev-import');
        $tid = (int) $tenant->id;

        $source = ReviewImportSource::query()->withoutGlobalScopes()->create([
            'tenant_id' => $tid,
            'provider' => 'manual',
            'title' => 'CSV',
            'source_url' => 'https://example.com/manual',
            'status' => ReviewImportSourceStatus::READY,
        ]);

        $hash = ReviewImportDedupe::hashNoExternal('manual', 'Пётр', null, 'Текст отзыва для импорта достаточной длины.');
        $candidate = ReviewImportCandidate::query()->withoutGlobalScopes()->create([
            'tenant_id' => $tid,
            'review_import_source_id' => $source->id,
            'provider' => 'manual',
            'dedupe_hash' => $hash,
            'author_name' => 'Пётр',
            'body' => 'Текст отзыва для импорта достаточной длины.',
            'status' => ReviewImportCandidateStatus::NEW,
            'rating' => null,
        ]);

        $service = app(ReviewCandidateImportService::class);
        $ids = $service->importCandidates([$candidate], false);

        $this->assertCount(1, $ids);
        $candidate->refresh();
        $this->assertSame(ReviewImportCandidateStatus::IMPORTED, $candidate->status);
        $this->assertNotNull($candidate->imported_review_id);

        $this->assertDatabaseHas('reviews', [
            'id' => $candidate->imported_review_id,
            'tenant_id' => $tid,
            'body' => 'Текст отзыва для импорта достаточной длины.',
            'status' => 'draft',
            'source' => 'import',
        ]);
    }

    public function test_second_import_skips_already_imported_candidate(): void
    {
        Config::set('reviews.import.download_avatars', false);

        $tenant = $this->createTenantWithActiveDomain('rev-import2');
        $tid = (int) $tenant->id;

        $source = ReviewImportSource::query()->withoutGlobalScopes()->create([
            'tenant_id' => $tid,
            'provider' => 'manual',
            'title' => 'CSV',
            'source_url' => 'https://example.com/manual',
            'status' => ReviewImportSourceStatus::READY,
        ]);

        $hash = ReviewImportDedupe::hashNoExternal('manual', 'Анна', null, 'Другой текст отзыва для импорта в сервис.');
        $candidate = ReviewImportCandidate::query()->withoutGlobalScopes()->create([
            'tenant_id' => $tid,
            'review_import_source_id' => $source->id,
            'provider' => 'manual',
            'dedupe_hash' => $hash,
            'author_name' => 'Анна',
            'body' => 'Другой текст отзыва для импорта в сервис.',
            'status' => ReviewImportCandidateStatus::NEW,
        ]);

        $service = app(ReviewCandidateImportService::class);
        $first = $service->importCandidates([$candidate], false);
        $second = $service->importCandidates([$candidate->fresh()], false);

        $this->assertCount(1, $first);
        $this->assertSame([], $second);
    }
}
