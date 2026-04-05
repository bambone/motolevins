<?php

namespace App\Filament\Tenant\Pages;

use App\Models\PlatformProductChangelogEntry;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use UnitEnum;

class TenantProductChangelogPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $title = 'Что нового';

    protected static ?string $navigationLabel = 'История изменений';

    protected static ?string $slug = 'whats-new';

    protected static bool $shouldRegisterNavigation = false;

    protected static string|UnitEnum|null $navigationGroup = null;

    protected string $view = 'filament.tenant.pages.product-changelog';

    public static function canAccess(): bool
    {
        return \currentTenant() !== null;
    }

    /**
     * @return Collection<string, Collection<int, PlatformProductChangelogEntry>>
     */
    #[Computed]
    public function groupedEntries(): Collection
    {
        return PlatformProductChangelogEntry::groupedPublishedForDisplay();
    }
}
