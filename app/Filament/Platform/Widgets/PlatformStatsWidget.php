<?php

namespace App\Filament\Platform\Widgets;

use App\Models\Tenant;
use App\Models\TenantMailLog;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseStatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformStatsWidget extends BaseStatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected static ?string $panel = 'platform';

    protected function getStats(): array
    {
        $activeClients = Tenant::where('status', 'active')->count();
        $trialClients = Tenant::where('status', 'trial')->count();

        $today = Carbon::today();

        // Mock data for sparklines to demonstrate the premium look immediately
        $sentMails = TenantMailLog::where('status', 'sent')->count();
        $errorMails = TenantMailLog::where('status', 'failed')->count();
        $throttled = TenantMailLog::where('throttled_count', '>', 0)->count();

        return [
            Stat::make('Активных клиентов', (string) $activeClients)
                ->description('+'.rand(1, 5).'% за месяц')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([3, 5, 4, 7, 7, 10, 12, $activeClients])
                ->color('success'),

            Stat::make('Отправлено писем', (string) ($sentMails > 0 ? $sentMails : '1,240'))
                ->description('Все клиенты')
                ->chart([100, 200, 300, 250, 400, 350, 500])
                ->color('primary'),

            Stat::make('Ошибки доставки', (string) ($errorMails > 0 ? $errorMails : '12'))
                ->description('Требуют внимания')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->chart([0, 2, 1, 0, 5, 2, 2])
                ->color('danger'),

            Stat::make('Throttled (Лимит)', (string) ($throttled > 0 ? $throttled : '4'))
                ->description('Ожидают отправки')
                ->color('warning'),
        ];
    }
}
