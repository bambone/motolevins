<?php

namespace App\Filament\Tenant\Widgets;

use Filament\Widgets\Widget;

/**
 * Removed from the product; class retained so stale Livewire dashboard snapshots do not 500.
 */
class BusinessHealthWidget extends Widget
{
    protected static bool $isLazy = false;

    protected static bool $isDiscovered = false;

    protected string $view = 'filament.tenant.widgets.business-health-placeholder';

    protected int|string|array $columnSpan = 'full';
}
