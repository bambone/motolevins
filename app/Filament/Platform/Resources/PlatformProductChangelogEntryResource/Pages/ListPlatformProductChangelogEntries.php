<?php

namespace App\Filament\Platform\Resources\PlatformProductChangelogEntryResource\Pages;

use App\Filament\Platform\Resources\PlatformProductChangelogEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlatformProductChangelogEntries extends ListRecords
{
    protected static string $resource = PlatformProductChangelogEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
