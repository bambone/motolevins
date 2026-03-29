<?php

namespace App\Filament\Platform\Pages;

use App\Filament\Platform\Widgets\Mail\MailStatsOverviewWidget;
use App\Filament\Platform\Widgets\Mail\MailTenantActivityTableWidget;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class MailMonitoringDashboard extends Page
{
    protected static ?string $title = 'Почта и доставка';

    protected static ?string $navigationLabel = 'Обзор';

    protected static string|\UnitEnum|null $navigationGroup = 'Почта';

    protected static ?int $navigationSort = 0;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelopeOpen;

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema(fn (): array => $this->getWidgetsSchemaComponents([
                        MailStatsOverviewWidget::class,
                        MailTenantActivityTableWidget::class,
                    ])),
            ]);
    }
}
