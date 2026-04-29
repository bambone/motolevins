<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ReviewImportSourceResource\Pages;

use App\Filament\Tenant\Resources\ReviewImportSourceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReviewImportSources extends ListRecords
{
    protected static string $resource = ReviewImportSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
