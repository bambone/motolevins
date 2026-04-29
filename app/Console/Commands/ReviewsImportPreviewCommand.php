<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ReviewImportSource;
use App\Services\Reviews\Imports\ReviewImportPreviewService;
use Illuminate\Console\Command;

final class ReviewsImportPreviewCommand extends Command
{
    protected $signature = 'reviews:import-preview {source_id : review_import_sources.id}';

    protected $description = 'Run review import preview for a source (sync)';

    public function handle(ReviewImportPreviewService $preview): int
    {
        $id = (int) $this->argument('source_id');
        $source = ReviewImportSource::query()->withoutGlobalScopes()->find($id);
        if ($source === null) {
            $this->error('Source not found.');

            return self::FAILURE;
        }

        $run = $preview->runPreview($source);
        $this->info('Run #'.$run->id.' status='.$run->status.' candidates='.$run->candidate_count);

        return self::SUCCESS;
    }
}
