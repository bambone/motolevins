<?php

namespace App\Console\Commands;

use App\Support\PageBuilder\EditorialGalleryJsonAuditor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class AuditEditorialGallerySectionsCommand extends Command
{
    protected $signature = 'editorial-gallery:audit';

    protected $description = 'Проверить page_sections editorial_gallery на типичные ошибки в data_json (HTML в image_url, VK в video_url и т.д.)';

    public function handle(): int
    {
        $rows = DB::table('page_sections')
            ->where(function ($q): void {
                $q->where('section_type', 'editorial_gallery')
                    ->orWhere('section_key', 'like', 'editorial\_gallery%');
            })
            ->orderBy('id')
            ->get(['id', 'tenant_id', 'page_id', 'data_json']);

        if ($rows->isEmpty()) {
            $this->info('Секций editorial_gallery не найдено.');

            return self::SUCCESS;
        }

        $bad = 0;
        foreach ($rows as $row) {
            $data = json_decode((string) $row->data_json, true) ?: [];
            $issues = EditorialGalleryJsonAuditor::collectIssues($data);
            if ($issues === []) {
                continue;
            }
            $bad++;
            $this->warn('page_section id='.$row->id.' tenant_id='.$row->tenant_id.' page_id='.$row->page_id);
            foreach ($issues as $issue) {
                $this->line('  • '.$issue['path'].': '.$issue['message']);
            }
        }

        if ($bad === 0) {
            $this->info('Проблемных секций не найдено ('.$rows->count().' проверено).');
        } else {
            $this->warn('Секций с замечаниями: '.$bad.' из '.$rows->count().'.');
        }

        return self::SUCCESS;
    }
}
