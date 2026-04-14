<?php

namespace App\Filament\Platform\Widgets\Mail;

use App\Auth\AccessRoles;
use App\Models\Tenant;
use App\Models\TenantMailLog;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MailStatsOverviewWidget extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected static bool $isDiscovered = false;

    protected static ?string $panel = 'platform';

    protected ?string $heading = 'Сводка по почте';

    protected ?string $description = 'За сегодня и за последние 24 часа (UTC приложения).';

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->hasAnyRole(AccessRoles::platformRoles());
    }

    protected function getStats(): array
    {
        $today = now()->startOfDay();

        $sentToday = TenantMailLog::query()
            ->where('status', TenantMailLog::STATUS_SENT)
            ->where('sent_at', '>=', $today)
            ->count();

        $failedToday = TenantMailLog::query()
            ->where('status', TenantMailLog::STATUS_FAILED)
            ->where('failed_at', '>=', $today)
            ->count();

        $throttleHitsToday = (int) TenantMailLog::query()
            ->where('created_at', '>=', $today)
            ->sum('throttled_count');

        $deferredNow = TenantMailLog::query()
            ->where('status', TenantMailLog::STATUS_DEFERRED)
            ->count();

        $queuedNow = TenantMailLog::query()
            ->where('status', TenantMailLog::STATUS_QUEUED)
            ->count();

        $activeTenants24h = (int) TenantMailLog::query()
            ->where('status', TenantMailLog::STATUS_SENT)
            ->where('sent_at', '>=', now()->subDay())
            ->distinct()
            ->count('tenant_id');

        $sent7d = TenantMailLog::query()
            ->where('status', TenantMailLog::STATUS_SENT)
            ->where('sent_at', '>=', now()->subDays(7))
            ->count();

        $failed7d = TenantMailLog::query()
            ->where('status', TenantMailLog::STATUS_FAILED)
            ->where('failed_at', '>=', now()->subDays(7))
            ->count();

        $topTenantId = TenantMailLog::query()
            ->select('tenant_id', DB::raw('count(*) as c'))
            ->where('status', TenantMailLog::STATUS_SENT)
            ->where('sent_at', '>=', now()->subDay())
            ->groupBy('tenant_id')
            ->orderByDesc('c')
            ->value('tenant_id');

        $topTenantLabel = $topTenantId
            ? (string) (Tenant::query()->whereKey($topTenantId)->value('name') ?? '#'.$topTenantId)
            : '—';

        return [
            Stat::make('Отправлено сегодня', (string) $sentToday)
                ->description('Успешные отправки')
                ->color('success'),
            Stat::make('Ошибок сегодня', (string) $failedToday)
                ->description('Статус failed')
                ->color($failedToday > 0 ? 'danger' : 'gray'),
            Stat::make('Throttled сегодня', (string) $throttleHitsToday)
                ->description('Сумма счётчиков отложек')
                ->color($throttleHitsToday > 0 ? 'warning' : 'gray'),
            Stat::make('В очереди / отложено', (string) ($queuedNow + $deferredNow))
                ->description('queued + deferred сейчас')
                ->color($deferredNow > 0 ? 'warning' : 'primary'),
            Stat::make('Активных клиентов (24ч)', (string) $activeTenants24h)
                ->description('Отправляли письма')
                ->color('info'),
            Stat::make('Топ клиент (24ч)', $topTenantLabel)
                ->description('По числу отправленных')
                ->color('gray'),
            Stat::make('Отправлено 7 дней', (string) $sent7d)
                ->description('Успешные')
                ->color('primary'),
            Stat::make('Ошибок 7 дней', (string) $failed7d)
                ->description('Failed')
                ->color($failed7d > 0 ? 'danger' : 'gray'),
        ];
    }
}
